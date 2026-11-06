# 08 — Qualidade, Testes, CI/CD, Observabilidade e Segurança

> **Versão:** 0.3 (revisão enxuta: foco em testes Pest de domínio/isolamento + CI rápido) · **Data:** 06/11/2026 · Depende de: `02-requisitos.md`, `04-backend-rotas.md`, `05-frontend-telas.md`, `06-infra-docker.md`

## 1. Princípios

| Princípio | Aplicação |
| :--- | :--- |
| **Qualidade proporcional às horas** | Testar **o que dói se quebrar**: isolamento entre usuários (IDOR), dinheiro em centavos, numeração e PDF de recibos, relatórios, exclusão de conta e backup. |
| **Teste no nível mais barato e rápido** | Regras em classes puras (unitário) + rotas com `assertInertia` (feature) no Pest rodando em SQLite (< 5 s para toda a suíte). |
| **Mesmo motor em teste e produção** | Testes rodam em SQLite (em memória para velocidade; em arquivo temporário para o teste de concorrência e de backup). |
| **CI enxuto e rápido** | Pipeline com Pint, `vue-tsc`, build do Vite e Pest. Sem ferramentas pesadas que atrasem o desenvolvimento individual. |

## 2. Pirâmide de testes

| Nível | Ferramenta | Foco | Quando roda |
| :--- | :--- | :--- | :--- |
| Unitário | Pest | `Money`, `AmountInWords`, `CpfCnpj`, `PhoneNumber`, `TextNormalizer`, transições de status | A cada commit |
| Feature (rotas) | Pest + `assertInertia` | Para cada rota: sucesso, validação (`assertSessionHasErrors`), autorização e **isolamento (IDOR)** | A cada commit |
| Consultas | Pest | Relatórios e dashboard com massa de dados conhecida (`strftime`, `SUM(CASE…)`) | A cada commit |
| Manual | Checklist | PDF em leitores diversos, compartilhamento no celular (Web Share / `wa.me`), responsividade 360 px | Sprints 4 e 6 |
| Usabilidade | Protocolo do §6 | Meta **> 90%** de sucesso (TAP §2, ≥ 28 de 30 tentativas) | Sprints 3 (informal), 5 e 6 |

## 3. Ferramentas

| Categoria | Ferramenta | Observação |
| :--- | :--- | :--- |
| Testes PHP | **Pest** (+ plugin Laravel) | Rápido e expressivo |
| Estilo PHP | **Laravel Pint** | Mantém código padronizado |
| Tipos e Build JS/TS | `vue-tsc` + `vite build` | Garante ausência de erros de tipo e empacotamento nas páginas Vue |
| Dados de teste | Factories + `DemoDataSeeder` | Sem dados pessoais reais |

## 4. Testes críticos

### 4.1 Testes automatizados prioritários

| # | Teste | Por quê | Tipo |
| :--- | :--- | :--- | :--- |
| T-01 | **Isolamento (IDOR):** usuário B recebe 404 ao ler, editar, excluir ou emitir recibo de recursos do usuário A (clientes, atendimentos, lançamentos, recibos, categorias, PDF) | RN-01; principal risco de segurança | Feature |
| T-02 | **Numeração concorrente de recibos:** N emissões simultâneas (processos separados sobre **arquivo** SQLite temporário) geram números consecutivos, sem duplicidade e sem erro visível | RN-19, RNF-19, R-09 | Feature/integração |
| T-03 | **1 recibo ativo por receita:** segunda emissão falha; após cancelar, nova emissão gera novo número | RN-08, RN-09 | Feature |
| T-04 | **Valor por extenso:** 1; 1,01; 2; 21; 100; 1.000; 1.250,75; 1.000.000; 999.999.999 (centavos exatos) | Documento com valor legal | Unitário |
| T-05 | **Transições de status do atendimento** válidas e inválidas; cancelamento com motivo | RN-04, RN-05 | Unitário/Feature |
| T-06 | **Snapshot imutável:** alterar cliente ou perfil depois da emissão não muda o recibo | ADR-08 | Feature |
| T-07 | **Relatórios:** massa conhecida confere receitas, despesas, líquido, agrupamento dia/semana/mês (semana ISO), top clientes (≥ 2 concluídos), cancelados fora da receita | RN-11 a RN-14 | Consulta |
| T-08 | **Lançamento com recibo:** campos bloqueados e exclusão negada (RN-22); herança de cliente do atendimento (RN-07) | Integridade | Feature |
| T-09 | **Configuração do SQLite:** `PRAGMA journal_mode` = `wal`, `foreign_keys` = 1, `busy_timeout` ≥ 5000 | Config crítica (se o Laravel não aplicar, o teste denuncia) | Feature |
| T-10 | **Chaves estrangeiras ativas:** excluir usuário remove dados em cascata; inserir `client_id` inexistente falha | Integridade | Feature |
| T-11 | **Busca sem acento:** "joao" encontra "João"; telefone por dígitos | PV-30 | Feature |
| T-12 | **Backup:** `backup:run` gera arquivo compactado que abre e passa `integrity_check`; retenção 7/4/3 mantém os certos e apaga o restante; `backup:verify` detecta arquivo corrompido | ADR-21 | Feature/integração |
| T-13 | **Restauração:** restaurar um backup em arquivo temporário devolve as mesmas contagens de linhas | RNF-13 | Integração |
| T-14 | **URL assinada:** expira; assinatura adulterada gera 403; limite de requisições por IP | RN-15 | Feature |
| T-15 | **LGPD:** exclusão de conta remove linhas e arquivos (logo, PDFs); anonimização limpa nome, telefone, e-mail, documento e `search_name`, preservando snapshots | RN-16 | Feature |
| T-16 | **Validações de entrada:** CPF/CNPJ, telefone BR, limites de tamanho, data futura de lançamento (RN-03), valor ≤ limite | Regras de negócio | Feature |
| T-17 | **Autenticação:** *throttle* de login, sessão expirada leva ao login com aviso (419), conta bloqueada não entra, resposta neutra em recuperação de senha | RN-17 | Feature |
| T-18 | **PDF:** gerado sem recursos remotos; contém número, valor, texto "não fiscal"; conteúdo do usuário escapado (ex.: `<script>` no nome) | ADR-09, XSS | Feature |
| T-19 | **E-mail do recibo:** job enfileirado, entrega registrada `queued → sent/failed`; nova tentativa em falha | RF-REC | Feature |
| T-20 | **Páginas de erro:** 403/404/419/429/500 renderizam `Error.vue` sem vazar detalhes | RNF-06 | Feature |

**Nota técnica sobre SQLite em teste:** o banco em memória é ótimo para a maioria, mas **testes de concorrência e de backup usam arquivo temporário** (memória não é compartilhada entre processos e não tem WAL de verdade).

### 4.2 Fluxos de navegador (E2E)

| # | Fluxo | Passos essenciais |
| :--- | :--- | :--- |
| E-01 | Cadastro → perfil do negócio | Criar conta com aceite, preencher emitente, ver checklist concluído |
| E-02 | Atendimento até recibo (F2) | Novo cliente inline → atendimento → concluir → receita Pix → emitir recibo → baixar PDF |
| E-03 | Lançamento rápido | Botão "+" → despesa em ≤ 3 toques, em viewport de 360 px |
| E-04 | Relatório do mês | Abrir faturamento, conferir totais e tabela equivalente ao gráfico |
| E-05 | Recuperação de acesso | Esqueci a senha (Mailpit) → redefinir → entrar |

Cada fluxo roda também em WebKit (Safari) quando o tempo permitir (RNF-08).

## 5. Massa de dados

| Conjunto | Uso |
| :--- | :--- |
| Factories | Testes automatizados (usuários A e B sempre criados juntos para isolamento) |
| `DemoDataSeeder` | Demonstração e testes de usabilidade: 1 prestador, ~25 clientes fictícios, ~60 atendimentos, ~150 lançamentos, recibos emitidos |
| Massa de desempenho | 5.000 lançamentos e 2.000 atendimentos de um usuário, gerada por comando de desenvolvimento, para medir RNF-03 |
| Massa de relatório | Conjunto pequeno, determinístico e conferido à mão (T-07) |

Nada de dados reais de clientes em desenvolvimento ou testes.

## 6. Protocolo de testes de usabilidade

| Item | Definição (proposta — **PV-26**, **PV-27**) |
| :--- | :--- |
| Objetivo | Medir se o fluxo completo é concluído com sucesso e sem ajuda (meta TAP: **> 90%**) |
| Participantes | ~5 prestadores de serviço do perfil-alvo (autônomos, pouca familiaridade com sistemas) por rodada; ao menos 2 usam o celular como aparelho principal |
| Ambiente | Aplicação no ar com dados de demonstração; celular do próprio participante; sessão de ~20 min, observada, com pensamento em voz alta |
| Consentimento | Termo curto (LGPD): finalidade, sem coleta de dados pessoais além do necessário, gravação só com autorização (**PV-27**) |
| Tarefas | T1 cadastrar cliente · T2 registrar atendimento · T3 lançar receita em Pix · T4 lançar despesa · T5 emitir e baixar recibo · T6 consultar o faturamento do mês |
| Sucesso de uma tentativa | Concluiu a tarefa sozinho, sem erro fatal, em até 2 tentativas e sem intervenção do moderador |
| **Meta** | O TAP pede **> 90%**. Com 5 participantes × 6 tarefas = 30 tentativas, 27 sucessos dão exatamente 90% (não supera); portanto a meta operacional é **≥ 28 de 30 (≈ 93%)**. Se o número de participantes ou tarefas mudar, recalcular o mínimo para ficar estritamente acima de 90% |
| Métricas adicionais | Tempo por tarefa, erros, pontos de hesitação, SUS opcional (10 perguntas) |
| Registro | Planilha por participante/tarefa, severidade dos problemas (crítico / grave / leve) e decisão (corrigir agora / depois) |
| Cronograma | **Informal** na sem 8–9 (1–2 pessoas, só Caixa); **Rodada 1** na sem 10; **Rodada 2** na sem 11, após correções |
| Critério de saída | Meta atingida na rodada 2, ou desvio registrado com plano de correção |

## 7. CI/CD

### 7.1 Pipeline enxuto (GitHub Actions)

| Etapa | Conteúdo | Falha bloqueia? |
| :--- | :--- | :---: |
| 1. Estilo e tipos | `vendor/bin/pint --test` e `npm run build` (inclui `vue-tsc`) | Sim |
| 2. Testes automatizados | `php artisan test` (Pest: unitários, feature, relatórios e isolamento) | Sim |
| 3. Deploy | Manual no servidor via `./scripts/deploy.sh` (com backup pré-deploy incluso) | — |

Cache de dependências (Composer e npm) mantém o pipeline inteiro executando em **~1 a 2 minutos**.

### 7.2 Fluxo de trabalho

| Tema | Definição |
| :--- | :--- |
| Branches | Trunk-based: `main` sempre implantável; branches curtas por tarefa; Pull Request mesmo trabalhando sozinho (aciona o CI e cria histórico) |
| Commits | Conventional Commits (`feat:`, `fix:`, `chore:`, `docs:`, `test:`) |
| Versões | Tags semânticas (`v0.x.y` até o MVP; `v1.0.0` na entrega) |
| *Hooks* locais (opcional) | Pint e ESLint antes do commit; gitleaks |
| Dependências | Dependabot semanal (Composer, npm, GitHub Actions, imagem base) |
| Migrations | Sempre com `down` funcional; testadas subindo e descendo em SQLite |

## 8. Observabilidade e operação

| Recurso | Definição |
| :--- | :--- |
| Logs | JSON em `stderr`; `X-Request-Id` em toda resposta; sem senha, token, telefone ou e-mail completos; nível `warning` em produção, `debug` só em dev |
| Erros | Páginas de erro com identificador da requisição; Sentry opcional (Laravel + Vue), sem PII |
| Saúde | `/up` monitorado por serviço externo gratuito de *uptime* (conferir plano vigente); *heartbeat* do scheduler |
| Fila | Alerta em log para `failed_jobs`; `queue:prune-failed` semanal |
| Banco | `sqlite:check` semanal; tamanho do arquivo e espaço livre em disco no log semanal; alerta em 80% |
| Backup | Sucesso/falha de `backup:run`, `backup:offsite`, `backup:verify` registrados; alerta se o último envio off-site tiver > 48 h |
| Desempenho | Tempo de resposta por rota em log (amostragem); `Model::shouldBeStrict()` em dev/CI detecta N+1 |
| Painel semanal | Revisão de 10 min no Sprint: erros, jobs falhos, tempo de backup, disco |

## 9. Checklist de segurança (OWASP Top 10 aplicado)

| Área | Verificação | Onde testar |
| :--- | :--- | :--- |
| Controle de acesso | Policies + escopo por `user_id`; 404 para recursos alheios; rotas admin protegidas | T-01, T-08 |
| Autenticação | Senha ≥ 8 (+ `uncompromised`), *throttle* de login, sessão com expiração e regeneração no login, sem enumeração de contas na recuperação | T-17 |
| Sessão e CSRF | Cookie `HttpOnly`, `Secure`, `SameSite=Lax`; CSRF automático do Inertia em todos os formulários | T-17 |
| XSS | Vue escapa por padrão; **proibido `v-html`** com conteúdo do usuário (regra de lint); Blade do PDF e e-mails com escape; CSP restritiva | T-18, revisão |
| Injeção SQL | Eloquent/Query Builder com parâmetros; SQL bruto (`strftime`, `VACUUM INTO`, índices) somente com valores fixos ou parâmetros | Revisão de `app/Queries` e migrations |
| Mass assignment | `$fillable` explícito; `user_id` nunca vem do formulário | Revisão |
| Upload (logo) | PNG/JPG/WebP, ≤ 1 MB, dimensões máximas, sem SVG, reprocessada (remove metadados), nome gerado, armazenamento privado | Feature |
| Downloads | PDF sempre por rota autenticada ou URL assinada com expiração e *throttle* | T-14 |
| DomPDF | Recursos remotos desativados; HTML do recibo sem conteúdo não escapado | T-18 |
| Cabeçalhos | HSTS (com TLS), `X-Content-Type-Options`, `Referrer-Policy`, `frame-ancestors 'none'`, CSP compatível com Vite/Inertia | Teste de cabeçalhos no smoke |
| Limites de requisição | Login, cadastro, recuperação, envio de recibo, PDF público | T-14, T-17 |
| Segredos | `.env` fora do repositório; `APP_KEY` e chaves do `rclone` em gerenciador de senhas; gitleaks | CI |
| Dependências | `composer audit`, `npm audit`, Dependabot, Trivy | CI |
| Arquivo do banco | Fora de `public/`; permissão restrita; volume não exposto | Revisão de deploy |
| Backups | Criptografados antes de sair do servidor; acesso restrito; retenção conhecida | T-12, runbook |
| Logs | Sem dados pessoais em texto livre; `APP_DEBUG=false` em produção | Revisão |
| Contêineres | Usuário não-root, imagens com tags fixas, sem portas desnecessárias | `06` §10 |
| LGPD | Termos, política, exclusão de conta, anonimização, retenção dos backups documentada | T-15, **PV-14** |

Critério de aceite (Sprint 6): **nenhum item crítico ou alto aberto** na revisão final.

## 10. Desempenho e acessibilidade

| Tema | Meta | Como medir |
| :--- | :--- | :--- |
| Leitura | p95 ≤ 500 ms no servidor | Log de tempo por rota com massa de desempenho |
| Relatórios | ≤ 1,5 s com 5.000 lançamentos por usuário | Teste com a massa de desempenho; se falhar: índice adequado → cache em `file` |
| Emissão de recibo | p95 ≤ 5 s (inclui PDF) | Teste manual/automatizado cronometrado |
| Carga inicial | LCP ≤ 2,5 s em 4G | Lighthouse (celular emulado) |
| Concorrência | 10 usuários simultâneos sem erro de bloqueio | Script simples de carga (ex.: `k6` ou `ab`) sobre lançamentos e emissão |
| Acessibilidade | WCAG 2.1 AA nos fluxos principais | axe nos fluxos E-01 a E-04 + teclado + leitor de tela (amostra) |

## 11. Compatibilidade (TAP, Risco 02)

| Item | Cobertura |
| :--- | :--- |
| Navegadores | Duas últimas versões de Chrome, Edge, Firefox e Safari (desktop e mobile) |
| Viewports | 360, 768 e 1280 px |
| PDF | Abrir e imprimir em Chrome, Firefox, Safari/iOS, leitor nativo do Android e Adobe |
| Compartilhamento | Web Share API com arquivo no Android/iOS; *fallback* `wa.me` no desktop; copiar link |
| Rede lenta | Emulação 4G lenta nas telas principais |

## 12. Qualidade por sprint (resumo)

| Sprint | Entrada de qualidade |
| :--- | :--- |
| S0 | CI, Pint, Larastan, ESLint, `vue-tsc`, T-09, T-10, T-12 (versão básica) |
| S1 | T-01 (clientes), T-11, T-16, T-17, E-01, E-05 |
| S2 | T-05, T-01 (atendimentos), E-02 parcial |
| S3 | T-08, T-16 (lançamentos), E-03; **usabilidade informal** |
| S4 | T-02, T-03, T-04, T-06, T-14, T-18, T-19; checklist de PDF e compartilhamento |
| S5 | T-07, medição de desempenho, E-04, axe; **usabilidade rodada 1** |
| S6 | T-13, T-15, T-20, revisão do §9, `backup:verify` e ensaio de restauração; **usabilidade rodada 2** |

## 13. Pontos a validar

| PV | Assunto |
| :--- | :--- |
| PV-14 | Retenção de recibos e de backups frente à exclusão pedida pelo usuário |
| PV-26 | Metas numéricas de "ágil" (RNF-03) |
| PV-27 | Participantes, consentimento e registro dos testes de usabilidade |
