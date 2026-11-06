# 01 — Arquitetura

> **Versão:** 0.3 (revisão enxuta: Inertia + SQLite + container único) · **Data:** 06/11/2026 · Depende de: `00-visao-geral.md`, `02-requisitos.md`
> **Mudanças em relação à v0.2:** Simplificação radical para MVP acadêmico — `SESSION_DRIVER=file` (elimina contenção de escrita no SQLite), PDF gerado sob demanda a partir do snapshot imutável (sem máquina de estados de PDF em disco), execução síncrona/`defer()` sem necessidade de worker de fila separado, e logs de auditoria nativos em vez de tabelas extras (`audit_logs`, `receipt_deliveries`, `receipt_sequences`).

## 1. Visão geral

O GestAgil é um **monólito Laravel** que renderiza suas telas com **Inertia.js + Vue 3 (TypeScript)**. Não existe API pública nem SPA separada: o navegador conversa com rotas web do Laravel, e o Inertia entrega o componente Vue da página junto com as *props* (dados) em JSON.

- **Aplicação:** Laravel (PHP 8.3+) criado a partir do **starter kit oficial Vue** (Inertia, TypeScript, Tailwind, shadcn-vue, telas de autenticação e de configurações de conta).
- **Dados:** **SQLite** em modo WAL em um único arquivo, no volume Docker `db-data`. Sessões e cache usam o sistema de arquivos (`file`), deixando o SQLite livre de disputas de escrita a cada clique.
- **Arquivos:** Logos do emitente em disco privado (volume `app-storage`). PDFs de recibos são gerados sob demanda em memória (< 100 ms via DomPDF) a partir do snapshot imutável gravado no banco.
- **Processos:** uma única imagem Docker executada no container principal `app` (FrankenPHP), com rotina de backup diário (`VACUUM INTO`) via cron/scheduler leve.
- **Sem Redis, sem Nginx separado, sem banco em container, sem worker de fila obrigatório, sem container de frontend em produção.**

Princípios: simplicidade e baixa manutenção (1 desenvolvedor, baixo volume de usuários), paridade dev/prod, dados isolados por usuário, dinheiro sem ponto flutuante, documentos imutáveis, custo zero.

## 2. Diagrama textual

```
                 Navegador do prestador (celular ou desktop)
                                   │ HTTPS 443 / HTTP 8080
                     ┌─────────────▼──────────────┐
                     │ app  (FrankenPHP = Caddy+PHP)│  TLS automático (opcional, PV-11)
                     │  Laravel + Inertia + Vue    │  • rotas web e páginas Inertia
                     │  (assets do Vite embutidos) │  • Policies · Actions · Form Requests
                     │                             │  • PDF (DomPDF) sob demanda
                     │                             │  • E-mail via SMTP (sync/defer)
                     └───────┬─────────────┬───────┘
                             │             │
        volume db-data       │             │   volume app-storage
   ┌─────────────────────────▼──┐      ┌───▼──────────────────────┐
   │ database.sqlite (WAL)      │      │ Logos e sessões/cache    │
   │ apenas dados de domínio    │      └──────────────────────────┘
   └───────┬────────────────────┘
           │
           ▼  backup diário / pré-deploy (VACUUM INTO + gzip)
   ┌────────────────────────────┐
   │ volume backups             │
   └────────────────────────────┘

  Volumes: db-data │ app-storage │ backups │ caddy-data (se TLS no container)
  [dev] extras: vite (HMR :5173) │ mailpit (:8025)
```

## 3. Divisão de responsabilidades

Não há mais fronteira "frontend/backend" em projetos separados. A divisão passa a ser **camadas dentro do mesmo repositório**:

| Camada | Onde vive | Responsabilidade |
| :--- | :--- | :--- |
| Páginas e componentes | `resources/js` (Vue 3 + TS) | Interface, navegação, formatação (BRL, datas), feedback de UX, máscaras |
| Controllers | `app/Http/Controllers` | Receber a requisição, autorizar, chamar regra, devolver `Inertia::render` ou redirect |
| Validação | `app/Http/Requests` | **Fonte única de validação** (Laravel FormRequest, mensagens em pt-BR) |
| Regras de negócio | `app/Actions` (só onde há regra real) e Models | Emissão de recibo, lançamento vinculado a atendimento, transições de status |
| Consultas de relatório | `app/Queries` | SQL agregado explícito e testável |
| Infra | `docker/`, `compose*.yaml`, `scripts/` | Imagem, volumes, deploy, backup |

**Regra de ouro:** o servidor valida sempre; o navegador só dá feedback rápido (máscaras, `required`, `inputmode`). Isso elimina a duplicação de schemas (Zod) prevista antes.

## 4. Estilo arquitetural

**Monólito Laravel com a estrutura padrão do framework**, sem módulos empacotados e sem *repository pattern*.

```
Request → Middleware (sessão, auth, HandleInertiaRequests, throttle)
        → Controller (fino)
        → FormRequest (validação + authorize)
        → Action (só quando há regra/transação) ou Model direto
        → Eloquent (global scope por usuário) / Query classes (relatórios)
        → Inertia::render('Pagina', props)  |  redirect()->with('success', …)
```

- **Actions** apenas para: emitir/cancelar recibo, registrar lançamento vinculado, mudar status do atendimento, excluir conta (em ordem reversa de dependências). O restante (CRUD simples) fica no controller.
- **Multi-tenancy por linha:** toda tabela de domínio tem `user_id`; trait `BelongsToUser` aplica *global scope* e preenche o campo; Policies reforçam. Recurso de outro usuário devolve **404**.
- **Props compartilhadas** (`HandleInertiaRequests`): usuário logado, flag de perfil do negócio completo, mensagens *flash* (sucesso/erro).

## 5. Fluxos técnicos-chave

### 5.1 Login (sessão do Laravel, via starter kit)
1. `GET /login` → página Inertia com o formulário.
2. `POST /login` → valida, aplica *throttle*, cria a sessão (`file`), regenera o ID e redireciona ao destino pretendido.
3. Páginas seguintes recebem o usuário pelas props compartilhadas. O CSRF é tratado pelo Inertia automaticamente (cookie XSRF), sem endpoint dedicado.
4. Sessão expirada (`419`): o handler global redireciona para o login com aviso, em vez do modal padrão do Inertia.

### 5.2 Emissão de recibo
1. Formulário (S-16) envia `POST /recibos` com `transaction_id` e dados do pagador.
2. Action `IssueReceipt`: valida receita sem recibo ativo e perfil completo → abre **transação SQLite com escrita imediata (`BEGIN IMMEDIATE`)** → calcula o próximo número sequencial do ano (`COALESCE(MAX(number), 0) + 1` para `(user_id, year)`) → grava `receipts` com *snapshot* imutável (emitente, pagador, valor, descrição, forma de pagamento). Índice único `(user_id, year, number)` garante integridade absoluta.
3. Redireciona ao detalhe do recibo (S-17) com mensagem de sucesso.
4. Geração do PDF: renderizado sob demanda via `GET /recibos/{receipt}/pdf` (ou URL pública/assinada) diretamente a partir do snapshot da tabela `receipts` usando DomPDF (< 100 ms). Se o recibo estiver cancelado (`status = canceled`), o template Blade aplica automaticamente a tarja "CANCELADO".
5. Compartilhamento: o navegador usa a Web Share API com o PDF ou abre `wa.me` com o link público de verificação/download; envio por e-mail despachado após a resposta (`defer()` / síncrono).

### 5.3 Relatórios e dashboard
Controller chama a Query class → SQL agregado (`strftime`, `SUM(CASE WHEN …)`) restrito ao `user_id` → props da página. **Sem cache de dados no MVP** (SQLite com índices atende com folga a meta de < 100 ms com 5.000 lançamentos).

### 5.4 Backup
Comando `php artisan backup:run` executa `VACUUM INTO` gerando cópia consistente do SQLite → compacta com `gzip` → remove backups antigos (> 14 dias). Executado antes de cada deploy e diariamente. Detalhes em `06`.

## 6. Estratégias transversais

| Tema | Estratégia |
| :--- | :--- |
| **Autenticação** | Sessão do Laravel (cookie `HttpOnly`, `SameSite=Lax`, `Secure` em produção), CSRF automático do Inertia, senha com hash forte, recuperação por e-mail. |
| **Autorização** | Policies por modelo + *global scope* por `user_id`; testes de isolamento obrigatórios (IDOR). |
| **Dinheiro** | Inteiro em centavos (`bigint` lógico; `integer` no SQLite, 64 bits), BRL único. Conversão para exibição no navegador (`Intl.NumberFormat('pt-BR')`) e no PDF (helper `Money`). |
| **Datas e fuso** | `APP_TIMEZONE=America/Recife` desde o primeiro dia. O fuso não tem horário de verão, então datas/horas são guardadas **em horário local**, sem conversões. `occurred_on` é `date`. **Não alterar o fuso depois** (PV-10, ADR-22). |
| **Cache, sessão e fila** | Cache e sessão em `file` (`storage/framework/{cache,sessions}` no volume `app-storage`). Fila em `sync` (ou `defer()` após envio da resposta HTTP). Isso **elimina escritas concorrentes desnecessárias no SQLite** e dispensa container `queue`. |
| **Arquivos** | Disco `local` privado para logos; PDFs gerados sob demanda a partir do snapshot no banco (zero arquivos órfãos em disco). |
| **Erros** | Páginas Inertia de erro (403/404/419/429/500/503); `ValidationException` vira erros por campo. |
| **Logs** | Canal `stderr` (Monolog) para eventos importantes (login, emissão/cancelamento de recibo, exclusão de conta), sem dados sensíveis. |
| **Rotas** | Web, nomeadas em inglês e URLs de tela em pt-BR (ver `05`); sem versionamento de API porque não há API. |
| **Idempotência** | Restrições únicas no banco (ex.: 1 recibo ativo por receita) e bloqueio de duplo clique no formulário. |
| **Integridade histórica** | Soft deletes em clientes, atendimentos e lançamentos; recibos com snapshot imutável. |
| **Concorrência (SQLite)** | Um escritor por vez; modo WAL + `busy_timeout=5000` + transações de escrita imediata; ver `06` e `08`. |

## 7. Decisões arquiteturais (ADRs resumidos)

| ID | Decisão | Justificativa | Alternativas descartadas | Consequência |
| :--- | :--- | :--- | :--- | :--- |
| ADR-01 | **Repositório único**, projeto Laravel na raiz (+ `docker/`, `scripts/`) | Um desenvolvedor; menos configuração; CI único | Monorepo com `backend/` e `frontend/` | Node e PHP convivem no mesmo projeto |
| ADR-02 | **Monólito Laravel + Inertia + Vue 3** | Uma base de código, sem contrato de API, sem CORS, sem duplicar validação e autenticação | SPA Vue + API REST | Não há API para app nativo futuro (ver ganchos de evolução) |
| ADR-03 | **Autenticação por sessão do starter kit** | Já pronta e testada (login, cadastro, recuperação); sem token em `localStorage` | Sanctum SPA; JWT | Depende da versão do starter kit vigente no Sprint 0 |
| ADR-04 | **Estrutura padrão do Laravel** | Prazo e equipe de 1 pessoa | Módulos empacotados; microsserviços | Fronteiras entre módulos por convenção de nomes |
| ADR-05 | **SQLite em modo WAL** | Baixo volume; zero servidor de banco; backup simples; testes usam o mesmo motor | PostgreSQL/MySQL | Um escritor por vez; exige disco persistente e backup com `VACUUM INTO` |
| ADR-06 | **Sessão e cache em `file`, fila em `sync`/`defer`, sem Redis** | Elimina contenção de escrita no SQLite e dispensa container `queue` | Sessão/fila no SQLite; Redis | Sessões ficam no volume `app-storage` |
| ADR-07 | **Centavos + BRL único** | Evita erros de ponto flutuante | `decimal(12,2)` | Conversão obrigatória nas bordas |
| ADR-08 | **Snapshot imutável no recibo** | Documento não muda se cliente/perfil mudar depois | Recibo com dados vivos | Duplicação controlada |
| ADR-09 | **DomPDF** | Sem Chromium; leve; suficiente para 1 página | Browsershot/Chromium; TCPDF | CSS limitado; recursos remotos desativados |
| ADR-10 | **PDF gerado sob demanda a partir do snapshot** | Zero gestão de arquivos PDF em disco, sem estados `pending/failed`, cancelamento reflete na hora | Salvar PDF em disco + jobs de fila | Renderização (< 100 ms) a cada download |
| ADR-11 | **Compartilhamento: Web Share API + `wa.me` + link público + e-mail** | Sem custo e sem API oficial do WhatsApp | WhatsApp Business Cloud API | Sem confirmação de entrega (**PV-05**) |
| ADR-12 | **Estado via props do Inertia + `useForm`** | O servidor é a fonte da verdade; menos bibliotecas | TanStack Query; Pinia | Estado de UI local com `ref`/`reactive` |
| ADR-13 | **Tailwind + shadcn-vue do starter kit** | Já vem configurado; alinhado ao TAP | PrimeVue/Vuetify | Tabelas e combobox montados com componentes base |
| ADR-14 | **Rotas web nomeadas; sem API e sem OpenAPI** | Não há consumidor externo | API REST versionada | Documentação das rotas em `04` e `05` |
| ADR-15 | **Logs nativos em `stderr` (sem tabela `audit_logs`)** | Padrão 12-factor; evita manter subsistema próprio de auditoria em banco | Tabela polimórfica `audit_logs` | Auditoria consultada via logs do container |
| ADR-16 | **Soft delete + exclusão completa de conta em cascata controlada** | Preserva histórico operacional e atende LGPD de forma simples | Anonimização complexa por linha | Exclusão da conta limpa dados em ordem reversa |
| ADR-17 | **Container único com FrankenPHP (Caddy + PHP)** | Um processo web, TLS automático opcional, configuração mínima | Nginx + PHP-FPM + múltiplos workers | Deploy e operação extremamente simples |
| ADR-18 | **Migrations executadas no deploy, depois do backup** | Ordem controlada e reversível | Migrar sem backup | Passo explícito no script de deploy |
| ADR-19 | **Código em inglês, UI em pt-BR** | Padrão de mercado | Tudo em português | Glossário mantido em `00` |
| ADR-20 | **Sem repository pattern; Actions só onde há regra** | Menos camadas | Repositories; Actions em tudo | Controllers simples de CRUD podem usar Eloquent direto |
| ADR-21 | **Backup com `VACUUM INTO` + retenção simples** | Cópia consistente sem parar o sistema | Copiar o arquivo com o sistema em uso | Script enxuto e confiável |
| ADR-22 | **Fuso fixo `America/Recife`, datas locais** | Sem horário de verão; relatórios sem conversão de fuso | Guardar em UTC e converter | Mudar o fuso depois exige migração de dados |

## 8. Trade-offs principais

| Tema | Ganho | Custo | Mitigação |
| :--- | :--- | :--- | :--- |
| Inertia vs. SPA + API | Muito menos código, uma validação, sem CORS | Sem API reutilizável | Se um app nativo surgir, expor rotas de API em um segundo momento (ver §9) |
| SQLite vs. servidor de banco | Simplicidade e backup de um arquivo | Um escritor por vez; sem lock por linha; busca sem acento limitada | WAL, `busy_timeout`, transação imediata, coluna de busca normalizada, testes de concorrência |
| PDF síncrono vs. assíncrono | UX simples | Pico de latência | Medir p95; migrar para job se > 5 s |
| Sem Redis | Menos infraestrutura | Sessão e fila disputam o mesmo arquivo | Volume baixo; sessão em `file` como alternativa |
| VPS/PaaS único | Custo zero, controle total | Sem alta disponibilidade; disco precisa ser persistente | Backups automáticos e off-site; `restart: unless-stopped`; runbook (**PV-11**) |
| FrankenPHP vs. Nginx + FPM | Um processo, configuração mínima | Menos material de suporte | Documentar Nginx + FPM como alternativa |
| DomPDF vs. Chromium | Imagem leve | Menor fidelidade de CSS | Template simples testado em leitores diversos |
| Sem offline (TAP) | Menor complexidade | Depende de rede | Banner de conexão; formulários do Inertia preservam dados em erro |
| Sem criptografia por coluna | Busca e filtros simples | Dados de contato em texto no arquivo | Volume protegido, backups criptografados, acesso restrito (**PV-14**) |

## 9. Ganchos de evolução (fora do MVP)

- **API para app nativo:** adicionar rotas `/api` com Sanctum reutilizando Actions e Policies (a regra de negócio já está fora dos controllers).
- **Trocar SQLite por PostgreSQL:** Eloquent isola quase tudo; os pontos de atenção são `strftime` nos relatórios e a busca normalizada (concentrados em `app/Queries`).
- **Storage S3-compatível:** trocar apenas o disco do Laravel.
- **Multiusuário por conta:** introduzir `accounts` e migrar o escopo de `user_id` para `account_id`.
- **NFS-e:** módulo separado com provedores por município (fora do escopo do TAP).
