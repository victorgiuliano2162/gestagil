# 09 — Riscos, Decisões e Pontos a Validar

> **Versão:** 0.3 (revisão enxuta para execução rápida) · **Data:** 06/11/2026 · Depende de: `00` a `08`
> Consolida: decisões arquiteturais e de simplificação fechadas, matriz de riscos e pontos a validar (PVs).

## 1. Decisões já tomadas

| ID | Decisão | Data | Efeito | Detalhe em |
| :--- | :--- | :--- | :--- | :--- |
| D-01 | **Monólito Laravel + Inertia.js + Vue 3** (sem SPA/API separadas) | 29/09/2026 | Sem API REST, Sanctum, OpenAPI, Axios, TanStack Query; validação única no servidor | `01` ADR-02, ADR-03, ADR-12, ADR-14 |
| D-02 | **SQLite em modo WAL** como banco | 29/09/2026 | **Resolve PV-18**; exige disco persistente e backup com `VACUUM INTO` | `01` ADR-05, `03`, `06` |
| D-03 | **Sessão e cache em `file`; fila em `sync`/`defer`; sem Redis** | 06/11/2026 | **Elimina contenção de escrita no SQLite** a cada requisição e dispensa container `queue` | `01` ADR-06, `06` |
| D-04 | Starter kit oficial Vue com **shadcn-vue** + TypeScript | 29/09/2026 | **Resolve PV-19** | `05` |
| D-05 | **7 tabelas de domínio enxutas** (sem `receipt_sequences`, `receipt_deliveries`, `audit_logs`) | 06/11/2026 | Sequência de recibo via `MAX(number)+1` em `BEGIN IMMEDIATE`; auditoria em `stderr` | `03` |
| D-06 | **PDF gerado sob demanda em memória (< 100 ms)** a partir do snapshot | 06/11/2026 | Elimina armazenamento de PDFs em disco, máquina de estados `pending/failed` e jobs de regeneração | `01` ADR-10, `04` |
| D-07 | **Container único `app` (FrankenPHP)** em produção | 06/11/2026 | Deploy e manutenção mínimos; permissões preparadas no build | `06`, `01` ADR-17 |
| D-08 | **Ordem de migrations corrigida no Sprint 1** + exclusão de conta em ordem topológica reversa | 06/11/2026 | Cadastro já semeia categorias e cria perfil vazio no S1; `DeleteAccount` respeita FKs `RESTRICT` | `03` §4, §6 |
| D-09 | **Formato do número do recibo padronizado em `NNNN/AAAA`** + verificação pública em `/r/{code}` e `/r/{code}/pdf` | 06/11/2026 | Consistência entre PDF, telas e compartilhamento WhatsApp | `02` RN-19, `04` §5.6 |
| D-10 | Fuso fixo `America/Recife`, datas locais e UDF `unaccent()` no SQLite | 06/11/2026 | Relatórios sem conversão de fuso e busca sem acento em clientes e atendimentos | `01` ADR-22, `03` §1 |

## 2. Matriz de riscos

Escala: Probabilidade (P) e Impacto (I) em **B**aixa / **M**édia / **A**lta / **C**rítica. Exposição = combinação de P × I (🔴 alta · 🟠 média · 🟢 baixa). Riscos do TAP mantêm a referência (R01–R03).

| ID | Risco | TAP | P | I | Exp. | Mitigação | Gatilho / contingência | Revisão |
| :--- | :--- | :---: | :---: | :---: | :---: | :--- | :--- | :---: |
| R-01 | **Janela de desenvolvimento insuficiente:** o TAP reserva 48 h (sem 6–9) para um escopo que pede ~62 h | R01 | A | A | 🔴 | Levar **PV-13** ao orientador; Sprint 0 nas semanas 4–5; MoSCoW; plano de corte (`07` §7); economia da nova stack tratada como folga | Velocidade do Sprint 1 < 80% do planejado → acionar o corte de itens *Could/Should* na ordem definida | Fim de cada sprint |
| R-02 | **Indisponibilidade pessoal** (trabalho, TCC, família) reduz as 12 h/semana | R01 | M | A | 🔴 | Reporte semanal; horas reais registradas; tarefas pequenas e entregáveis por semana; folga da nova stack | Duas semanas seguidas < 8 h → reduzir escopo e avisar o orientador | Semanal |
| R-03 | **Incompatibilidade de navegadores e PDF** (Safari/iOS, leitores de PDF, Web Share) | R02 | M | M | 🟠 | Template de PDF simples; matriz de compatibilidade (`08` §11); *fallback* `wa.me`/copiar link; testes manuais no Sprint 4 | Falha de PDF em leitor comum → simplificar CSS/fonte; oferecer "abrir em nova aba" | Sprint 4 e 6 |
| R-04 | **Baixa adesão nos testes de usabilidade** | R03 | M | A | 🔴 | Recrutar ~8 para garantir 5; começar contatos na semana 6; sessões curtas e remotas possíveis; termo de consentimento simples | < 5 confirmados até a semana 9 → recrutar em outro grupo (familiares/comércio local) | Semana 6 e 9 |
| R-05 | **Hospedagem gratuita instável ou sem disco persistente** (incompatível com SQLite) | — | M | A | 🔴 | Decidir **PV-11** até M1; validar persistência com um deploy de teste no Sprint 0; plano B: máquina própria + túnel | Provedor com disco efêmero ou limites que quebram o uso → migrar para plano B | Sprint 0 |
| R-06 | **Perda ou corrupção de dados** (SQLite, volume apagado, backup falho) | — | B | C | 🟠 | Plano de backup (`06` §12): `VACUUM INTO`, pré-deploy, off-site criptografado, teste mensal; Makefile sem `down -v`; `integrity_check` semanal | `backup:verify` falhou ou último off-site > 48 h → corrigir antes de qualquer deploy | Mensal e Sprint 6 |
| R-07 | **Entregabilidade do e-mail** (SMTP gratuito, spam, limites) | — | M | M | 🟠 | Provedor com SPF/DKIM se houver domínio (**PV-12**); WhatsApp e download cobrem o requisito mínimo; e-mail de recuperação de senha testado cedo | Taxa de falha alta → trocar provedor; manter canais alternativos | Sprint 1 e 4 |
| R-08 | **DomPDF:** layout inconsistente ou vulnerabilidade da biblioteca | — | B | M | 🟢 | Template simples e testado (T-18); recursos remotos desativados; manter atualizado; `composer audit` | Alerta de segurança → atualizar imediatamente; considerar alternativa | Sprint 4 |
| R-09 | **Concorrência e bloqueios de escrita no SQLite** (numeração de recibos, sessão, fila) | — | M | A | 🔴 | WAL, `busy_timeout`, transações de escrita imediata, um worker de fila; T-02 e T-09; sessão em `file` como alternativa; medir com carga (`08` §10) | Erros "database is locked" em uso real ou teste → ajustar sessão/fila; se persistir, plano de saída para PostgreSQL (`01` §9) | Sprint 0, 4 e 6 |
| R-10 | **Curva de aprendizado** (Docker/FrankenPHP, Inertia, Pest) | — | M | M | 🟠 | Starter kit oficial; Laravel Sail como alternativa em dev; *time-box* de 12 h no Sprint 0; medir familiaridade (**PV-24**); alternativa Nginx+FPM | Sprint 0 estourou 30% → simplificar (usar Sail, adiar Larastan/E2E) | Fim do Sprint 0 |
| R-11 | **Não conformidade com a LGPD** | — | M | A | 🔴 | Coleta mínima; termos e política; exclusão de conta e anonimização; backups criptografados e retenção documentada; papéis definidos (**PV-14**) com o orientador | Dúvida jurídica sem resposta até o Sprint 5 → adotar a alternativa mais restritiva | Sprint 1 e 5 |
| R-12 | **Aumento de escopo** (novas ideias, pedidos dos testes) | — | A | M | 🟠 | Controle de mudanças (`07` §8); só entra se substituir item de mesmo peso; economia de horas não vira escopo | Backlog crescendo sem itens removidos → congelar escopo antes do previsto | Semanal |
| R-13 | **Ambiguidade legal do recibo** (confundido com documento fiscal) | — | M | M | 🟠 | Aviso "documento não fiscal" no PDF e na tela; texto revisado (**PV-15**) | Orientação contrária → ajustar o texto e o template | Sprint 4 |
| R-14 | **Dependências vulneráveis ou mudanças incompatíveis** (starter kit, pacotes, imagens) | — | M | M | 🟠 | Fixar versões; Dependabot; audits no CI; atualizar entre sprints, não durante | Vulnerabilidade alta → atualizar no mesmo dia; quebra de versão → manter a anterior | Semanal |
| R-15 | **Vazamento de dados entre contas (IDOR)** | — | B | C | 🟠 | *Global scope* + Policies + 404; T-01 para cada recurso; revisão de `app/Queries` | Teste T-01 falhou → bloqueia o merge; revisão manual antes do deploy | Contínuo |

*(O antigo R-09 "Sanctum/CORS" deixou de existir com o Inertia; o número foi reaproveitado para concorrência no SQLite.)*

### 2.1 Resposta aos riscos do TAP

| TAP | Risco | Onde tratado |
| :--- | :--- | :--- |
| R01 | Sobrecarga do responsável | R-01, R-02, `07` §1 e §7 |
| R02 | Incompatibilidade entre navegadores/geração de PDF | R-03, `08` §11 |
| R03 | Baixa adesão nos testes | R-04, `08` §6 |

## 3. Tabela mestre de pontos a validar (PV)

**Status:** ✅ resolvido · 🟡 decisão provisória em uso (aguarda validação) · ⚪ em aberto.
**Quem valida:** **O** orientador · **U** usuários-alvo · **D** você (desenvolvedor) · **T** teste/medição.

| PV | Assunto | Opções | Recomendação provisória | Quem | Prazo | Status |
| :--- | :--- | :--- | :--- | :---: | :---: | :---: |
| PV-01 | Perfis de acesso / Administrador | Só Prestador · + Admin mínimo | Só **Prestador** no MVP; Admin (métricas e bloqueio, sem ver dados financeiros) opcional | O | M1 | 🟡 |
| PV-02 | Semântica de data/hora do atendimento | Registro do que foi feito · agendamento futuro · ambos | Data/hora do serviço, pode ser futura; **sem agenda nem lembretes** | O, U | S2 | 🟡 |
| PV-03 | Valor do atendimento | Sem valor · valor previsto opcional · obrigatório | **Valor previsto opcional**; o valor real vem dos lançamentos | U | S2 | 🟡 |
| PV-04 | Vínculo recibo ↔ lançamento | 1:1 ativo · vários por lançamento · por atendimento | **1 recibo ativo por receita** | O | M1 | 🟡 |
| PV-05 | WhatsApp sem API oficial | Web Share + `wa.me` · API oficial (fora do escopo) | **Web Share (arquivo) → `wa.me` com link assinado**; e-mail como alternativa | O | M1 | 🟡 |
| PV-06 | Valores pendentes / "a receber" | Fora do MVP · incluir | **Fora do MVP**; campo `status` reservado | O, U | S3 | 🟡 |
| PV-07 | Formas de pagamento | Cartão único · crédito/débito | Pix, Dinheiro, **Cartão**, Outro | U | S3 | 🟡 |
| PV-08 | Definição de faturamento líquido | Receitas − despesas (caixa) · só receitas · com impostos | **Receitas − despesas, regime de caixa** | O | S5 | 🟡 |
| PV-09 | Cliente recorrente | ≥ 2 · ≥ 3 · por período | **≥ 2 atendimentos concluídos** no período | O, U | S5 | 🟡 |
| PV-10 | Semana e fuso | ISO/domingo-sábado; fuso local | **Semana ISO (segunda a domingo)**; **fuso fixo America/Recife** (ADR-22) | D | S5 | 🟡 |
| PV-11 | Hospedagem R$ 0 com **disco persistente**, domínio e TLS | VPS gratuita/créditos · máquina própria + túnel · PaaS com volume | Definir até M1; **validar persistência com deploy de teste no Sprint 0**; plano B: máquina própria + túnel | O, D | M1 / S0 | ⚪ |
| PV-12 | SMTP gratuito e domínio de envio | Provedor com plano gratuito · SMTP institucional | Provedor gratuito com SPF/DKIM; Mailpit em dev | D | S1 | ⚪ |
| PV-13 | Janela de desenvolvimento do TAP (48 h) vs. escopo (~62 h) | Manter 6–9 · antecipar Sprint 0 (sem 4–5) e estender até a sem 11 | **Antecipar Sprint 0 e estender**; se negado, cenário reduzido (`07` §7) | O | **M1** | ⚪ |
| PV-14 | LGPD: papéis, base legal, retenção de recibos, retenção de backups | Anonimizar preservando snapshot · apagar tudo | Prestador = controlador; plataforma = operador; exclusão de conta apaga tudo (backups expiram em ~90 dias); anonimização preserva snapshot | O | S1 e S5 | ⚪ |
| PV-15 | Aviso/validade do recibo; CPF/CNPJ obrigatório | Obrigatório · opcional | Aviso "documento não fiscal"; documento do emitente **obrigatório** para emitir | O | S4 | 🟡 |
| PV-16 | Verificação de e-mail e cadastro aberto | Aberto sem verificação · com verificação | Cadastro aberto com aceite de termos; **verificação opcional (Could)** | O, D | S1 | 🟡 |
| PV-17 | Exportações CSV/PDF e PWA | Incluir · adiar | **Could**; só se sobrar horas | D | S5 | 🟡 |
| PV-18 | Banco de dados | PostgreSQL · MySQL · SQLite | **SQLite** (decisão D-02) | — | — | ✅ |
| PV-19 | UI kit | shadcn-vue · PrimeVue | **shadcn-vue** do starter kit (D-04) | — | — | ✅ |
| PV-20 | Expiração do link assinado | 7 · 30 · 90 dias | **30 dias**, com *throttle* | O | S4 | 🟡 |
| PV-21 | Reabrir atendimento concluído/cancelado | Permitir · proibir | **Permitir**, com auditoria | U | S2 | 🟡 |
| PV-22 | Editar/excluir lançamento com recibo | Bloquear · permitir com recibo cancelado | **Bloquear**; cancelar o recibo antes | O, U | S4 | 🟡 |
| PV-23 | Lançamentos futuros | Proibir · permitir | **Proibir** no MVP (todo lançamento é realizado) | U | S3 | 🟡 |
| PV-24 | Familiaridade com Laravel/Vue/Docker/Inertia | — | Medir no Sprint 0; se o Docker consumir > 30% do tempo, usar Sail | D, T | S0 | ⚪ |
| PV-25 | Destino e criptografia do backup off-site | Google Drive · Backblaze B2 · Cloudflare R2 | **`rclone crypt`** sobre um destino gratuito; escolher o destino e criar a conta até o Sprint 0 | D | S0 | 🟡 (parcial) |
| PV-26 | Metas numéricas de "ágil" | p95 ≤ 500 ms leitura; ≤ 1,5 s relatórios | Metas de RNF-03 | O | S5 | 🟡 |
| PV-27 | Participantes, proto-personas e consentimento dos testes | — | 5 por rodada; termo curto; registro conforme `08` §6 | O | S3 | ⚪ |
| PV-28 | Escala esperada | ≤ 100 usuários / 10 simultâneos | Adotado; revisar se houver adesão maior (SQLite) | O | S5 | 🟡 |
| PV-29 | Telefone duplicado de clientes | Bloquear · avisar e permitir | **Avisar e permitir** (famílias compartilham telefone); índice **não único** | U | S1 | 🟡 |
| PV-30 | Busca sem acento | Coluna normalizada só em clientes · também em atendimentos · aceitar limitação | **Coluna `search_name` em clientes**; atendimentos com `LIKE` simples (limitação aceita no MVP) | D | S1 | 🟡 |

**PVs críticos (respostas necessárias até M1, 18/10/2026):** PV-13, PV-01, PV-04, PV-05, PV-11. Depois: PV-14 (Sprint 1), PV-25 (Sprint 0).

## 4. Trade-offs consolidados

| Tema | Ganho | Custo | Onde |
| :--- | :--- | :--- | :--- |
| Inertia em vez de SPA + API | Menos código, uma validação, sem CORS | Sem API para app nativo | `01` §8 |
| SQLite em vez de servidor de banco | Simplicidade e backup de um arquivo | Um escritor por vez; exige disco persistente e backup correto | `01` §8, `06` §12 |
| Sem Redis | Menos infraestrutura | Sessão e fila usam o mesmo arquivo do banco | `01` ADR-06 |
| PDF síncrono | UX simples | Latência na emissão | `01` ADR-10 |
| FrankenPHP | Um processo, TLS simples | Menos material de suporte que Nginx | `01` ADR-17, `06` §2 |
| Sem offline (TAP) | Menos complexidade | Depende de rede | `05` §8 |
| Sem criptografia por coluna | Busca e filtros simples | Dados de contato em texto no arquivo | `01` §8, **PV-14** |
| Escopo *Must* primeiro | MVP funcional em M6 | Recursos *Could* podem ficar de fora | `07` §7 |

## 5. Roteiro de perguntas

### 5.1 Ao orientador

| # | Pergunta | PV |
| :---: | :--- | :---: |
| 1 | Posso iniciar o Sprint 0 nas semanas 4–5 e estender o desenvolvimento até a semana 11? Caso contrário, aceita o escopo funcional reduzido descrito em `07` §7 como desvio registrado? | PV-13 |
| 2 | O recibo pode ser tratado como **documento não fiscal**, com esse aviso no PDF? CPF/CNPJ do emitente deve ser obrigatório? | PV-15, PV-04 |
| 3 | Enviar por WhatsApp via Web Share/`wa.me` (sem API oficial) satisfaz "envio direto"? | PV-05 |
| 4 | Como tratar a LGPD: posso considerar o prestador como controlador e a plataforma como operador? Quem revisa os Termos e a Política? | PV-14 |
| 5 | Hospedagem em máquina própria com túnel é aceitável se não houver VPS gratuita com disco persistente? | PV-11 |
| 6 | É necessário um perfil de Administrador? | PV-01 |
| 7 | Os testes de usabilidade devem ter formato específico (número de participantes, termo de consentimento)? A meta "> 90%" será medida por tentativas (≥ 28 de 30)? | PV-27 |
| 8 | Backups off-site em serviço gratuito de nuvem (criptografados) atendem à exigência de proteção de dados? | PV-25 |

### 5.2 Aos usuários-alvo (prestadores)

| # | Pergunta | PV |
| :---: | :--- | :---: |
| 1 | Você registra atendimentos **depois** de fazer o serviço ou **agenda antes**? | PV-02 |
| 2 | Você combina um valor antes do serviço? Onde anota hoje? | PV-03 |
| 3 | Quais formas de pagamento você recebe? Precisa separar cartão de crédito e débito? | PV-07 |
| 4 | Você costuma ter clientes que **pagam depois**? Isso precisa aparecer no sistema? | PV-06 |
| 5 | Seus clientes pedem recibo? Por onde você envia hoje (WhatsApp, e-mail, papel)? | PV-05 |
| 6 | Dois clientes podem ter o mesmo telefone (família, empresa)? | PV-29 |
| 7 | Quais categorias de receita e despesa você usa? | Categorias |
| 8 | Você reabriria um atendimento já concluído? Em que situação? | PV-21 |

## 6. Perguntas em aberto (técnicas)

| # | Pergunta | Quando resolver |
| :---: | :--- | :--- |
| 1 | Qual versão do Laravel e qual biblioteca de autenticação o starter kit Vue traz na data do Sprint 0? (ajusta `04` §3) | Sprint 0 |
| 2 | Quais opções de SQLite (`journal_mode`, `busy_timeout`, `transaction_mode`) a versão do Laravel aplica por padrão? (ajusta `04` §11) | Sprint 0 |
| 3 | FrankenPHP ou Nginx + PHP-FPM na imagem? | Sprint 0 (time-box de 2 h) |
| 4 | Como o kit gera rotas tipadas para o front (Wayfinder/Ziggy)? | Sprint 0 |
| 5 | Sessão em `database` ou `file`, se houver disputa de escrita? | Sprint 4 (medição) |
| 6 | Precisa de cache de relatórios? | Sprint 5 (medição com 5.000 lançamentos) |
| 7 | Qual provedor de *uptime* gratuito usar? | Sprint 6 |

## 7. Checklist final de próximos passos

### 7.1 Imediato (semanas 1 a 3, até M1 em 18/10/2026)

- ☐ Ler os 10 arquivos e marcar o que discorda (foco em `00`, `02`, `07`)
- ☐ Enviar ao orientador a lista de PVs críticos (**PV-13, PV-01, PV-04, PV-05, PV-11, PV-14**) com as recomendações provisórias
- ☐ Decidir a hospedagem com **disco persistente** (ou registrar o plano B: máquina própria + túnel)
- ☐ Escolher o provedor de SMTP e, se possível, um domínio/subdomínio
- ☐ Escolher o destino off-site do backup e criar a conta (Drive, B2 ou R2)
- ☐ Definir e configurar um **gerenciador de senhas** para `.env`/`APP_KEY` e chaves do `rclone`
- ☐ Criar o repositório no GitHub (privado) e ativar Dependabot
- ☐ Começar a recrutar participantes dos testes de usabilidade (meta: 8 nomes para garantir 5)
- ☐ Rascunhar Termos de Uso e Política de Privacidade (texto inicial)
- ☐ Ajustar o cronograma e o registro de horas conforme a resposta do orientador

### 7.2 Sprint 0 (semanas 4–5, ~12 h)

- ☐ **Spike de 2 h:** imagem Docker + starter kit + SQLite (WAL) rodando, com **deploy de teste em volume persistente** na hospedagem escolhida
- ☐ Escolher FrankenPHP ou Nginx + PHP-FPM e registrar (ADR-17)
- ☐ Configurar SQLite (WAL, `busy_timeout`, chaves estrangeiras) com o teste T-09
- ☐ Configurar Pest, Pint, Larastan, ESLint, Prettier e `vue-tsc`
- ☐ Criar pipeline no GitHub Actions (lint, análise, testes, build)
- ☐ Implementar `backup:run` básico e ver um `.sqlite.gz` válido
- ☐ Medir o tempo real do Sprint 0 e recalibrar as estimativas (fecha PV-24)
- ☐ Atualizar `04` e `06` com as respostas das perguntas em aberto 1 a 4

### 7.3 Ao longo dos sprints

- ☐ Sprint 1: T-01, T-11, T-17; responder PV-12, PV-16, PV-29, PV-30
- ☐ Sprint 3: usabilidade **informal**; definir participantes (PV-27)
- ☐ Sprint 4: T-02 (concorrência de recibos), matriz de compatibilidade de PDF
- ☐ Sprint 5: relatórios conferidos, massa de desempenho, **rodada 1** de usabilidade
- ☐ Sprint 6: backup completo (off-site + `backup:verify`), ensaio de restauração, revisão de segurança (`08` §9), **rodada 2**
- ☐ Semana 12: apresentação, relatório final, lições aprendidas e métricas SMART com evidências

### 7.4 Rotina semanal

- ☐ Reporte de status ao orientador (1 página: feito, próximo, riscos, horas)
- ☐ Revisão da matriz de riscos (gatilhos) e do painel operacional (`08` §8)
- ☐ Controle de mudanças: nada entra sem substituir item de mesmo peso
