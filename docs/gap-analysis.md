# Análise de Lacunas Funcionais — GestÁgil

- **Data da análise:** 22/11/2026
- **Escopo:** Repositório completo da aplicação web (`app/`, `routes/`, `database/`, `config/`, `resources/`, `docker/`, `scripts/`, `tests/` e documentação principal em `README.md` e `docs/`)
- **Commit analisado:** `e06a4ac`

---

## Diagnóstico Atual

O **GestÁgil** é uma aplicação monolítica *self-hosted* voltada a microempreendedores e prestadores de serviços (MEI) para gerir clientes, atendimentos, fluxo de caixa, emissão de recibos em PDF com verificação pública, relatórios e backup/restauração em JSON. A stack real é **Laravel 13 (`^13.17`) + PHP 8.3+/8.4 + Inertia.js 2 + Vue 3 (TypeScript) + Tailwind CSS + SQLite (WAL) + DomPDF + FrankenPHP/Docker**, no estágio de **MVP funcional avançado** (embora declarado "100% concluído" em `docs/10-relatorio-encerramento.md`).

Como **pontos fortes reais**, o projeto apresenta modelagem monetária consistente em centavos (`amount_cents`), isolamento multi-tenant via `BelongsToUser` + `UserScope`, conversor `AmountInWords`, compressão client-side de logotipos em Canvas, backup de SQLite com `VACUUM INTO` + `PRAGMA integrity_check` e 18 arquivos de testes Pest cobrindo fluxos principais.

**Fontes consultadas:** `README.md`, `docs/02-requisitos.md`, `docs/10-relatorio-encerramento.md`, `composer.json`, `package.json`, `.env.example`, migrations, models, controllers, FormRequests, actions, views Blade/Vue, configs Docker/scripts, suíte de testes e `git log` (commit `e06a4ac`).

---

## Escopo e Limites da Análise

Foi auditado todo o repositório web (backend `app/`, `routes/`, `database/`, `config/`, frontend `resources/js/` e `resources/views/`, infraestrutura `docker/`, `scripts/`, `compose*.yaml` e docs de referência). Ficaram de fora os documentos de planejamento futuro do app Android (`docs/12-*` a `docs/16-*`) e a execução completa da suíte Pest (o PHP local não possui a extensão `pdo_sqlite`, impedindo a execução dos 67 testes de feature; os achados foram verificados estaticamente no código). Assumiu-se o contexto declarado de aplicação local/pequeno piloto multi-usuário com isolamento estrito por `user_id` (sem painel admin nem multi-usuário por loja).

---

## Lacunas Identificadas

| # | Prioridade | Tipo | Categoria | Lacuna | Evidência | Impacto (e probabilidade) | Esforço | Confiança |
| :-- | :-- | :-- | :-- | :-- | :-- | :-- | :-- | :-- |
| 1 | **Alta** | Bug | Segurança | Restauração de backup permite sequestrar recibo de outro usuário por `code` (IDOR/overwrite cross-tenant) | `app/Actions/Backup/RestoreUserDataAction.php:250-274` | Como `/r/{code}` é público, qualquer usuário autenticado pode importar um JSON com o `code` de outro usuário e sobrescrever `user_id` e dados do recibo alheio; fácil de explorar em ambiente multi-usuário | P | Confirmado |
| 2 | **Alta** | Bug | Segurança | Validações `exists` de chaves estrangeiras não filtram por `user_id` | `app/Http/Requests/AppointmentRequest.php:34`, `app/Http/Requests/TransactionRequest.php:38-41`, `app/Http/Requests/IssueReceiptRequest.php:17` | Usuário pode vincular `client_id`, `appointment_id` ou `category_id` de outro tenant; em `app/Http/Controllers/ReportController.php:57`, o `leftJoin` expõe o nome da categoria alheia | P | Confirmado |
| 3 | **Alta** | Bug | Fluxo | Logo da loja nunca aparece no PDF do recibo e não é removido na exclusão de conta (LGPD) por divergência de disco (`local` × `public`) | `app/Http/Controllers/BusinessProfileController.php:50` salva em `local`, mas `app/Services/ReceiptPdfService.php:17-20` e `app/Http/Controllers/ProfileController.php:73` leem/excluem em `public` | 100% dos recibos PDF saem sem o logotipo do comerciante e arquivos privados permanecem órfãos após exclusão de conta; ocorre em todo uso normal | P | Confirmado |
| 4 | **Alta** | Bug | Dados | Exportação de backup acessa 7 colunas inexistentes em `BusinessProfile` e descarta 5 reais; `replace` não limpa soft-deletes | `app/Actions/Backup/ExportUserDataAction.php:134-150` vs `database/migrations/2026_11_06_105000_create_business_profiles_table.php`; `app/Actions/Backup/RestoreUserDataAction.php:319-325` | Em dev/test (`shouldBeStrict`), `/backup/export` lança `MissingAttributeException` para qualquer usuário cadastrado; em prod, perde endereço/atividade no backup. Em `replace`, registros com `deleted_at` causam erro de `UNIQUE(uuid)` na reimportação | P | Confirmado |
| 5 | **Alta** | Bug | Dados | Exclusão de lançamento com recibo ativo e de atendimento com vínculos não é bloqueada | `app/Http/Controllers/TransactionController.php:119-125`, `app/Http/Controllers/AppointmentController.php:149-155`, `app/Models/Transaction.php:56-59` | Viola RN-22 e RF-ATD-05: permite apagar receita que possui recibo oficial emitido, quebrando a rastreabilidade financeira; ocorre em uso comum | P | Confirmado |
| 6 | **Alta** | Bug | Fluxo | Data exibe `Invalid Date` na listagem do caixa e alternar Entrada/Saída não atualiza categorias | `resources/js/Pages/Transactions/Index.vue:189` concatena `'T12:00:00'` em ISO-8601 (`app/Models/Transaction.php:35`); `resources/js/Pages/Transactions/Create.vue:85-102` mantém categorias do tipo inicial (`app/Http/Controllers/TransactionController.php:66`) | Todas as datas do extrato ficam quebradas (`Invalid Date`) e despesas podem ser gravadas com categoria de receita (viola RN-20); ocorre em todo uso normal | P | Confirmado |
| 7 | **Alta** | Bug | Dados | Regras de negócio críticas ausentes na emissão de recibo e criação de lançamentos | `app/Http/Controllers/ReceiptController.php:86-155` (não exige CPF/CNPJ — RN-18; checa recibo ativo fora da transação — corrida RN-08); `app/Http/Controllers/TransactionController.php:99-117` e `app/Http/Requests/TransactionRequest.php:34-43` (aceita atendimento cancelado — RN-05, cliente divergente — RN-07, data futura — RN-03 e valor sem teto — RN-02) | Emite recibos incompletos ou duplicados sob concorrência e grava receitas inconsistentes com o atendimento; probabilidade moderada a alta no uso diário | P | Confirmado |
| 8 | **Alta** | Lacuna funcional | Fluxo | Fluxo principal `Atendimento → Caixa` desconectado na UI e CRUD de lançamentos sem edição | `routes/web.php:58` (`only(['index', 'create', 'store', 'destroy'])`); `resources/js/Pages/Appointments/Show.vue` e `app/Http/Controllers/AppointmentController.php:76-87` sem botão para lançar recebimento nem lista de lançamentos/recibos vinculados | Usuário não consegue corrigir um lançamento (RF-CX-06) nem concluir e faturar um atendimento diretamente pela tela de detalhe (RF-ATD-07), obrigando retrabalho manual | M | Confirmado |
| 9 | **Média** | Bug | Dados | Relatórios incluem transações excluídas (soft-deleted) no ranking de clientes e atendimentos cancelados no valor orçado | `app/Http/Controllers/ReportController.php:110-124` (`join('transactions')` sem `whereNull('transactions.deleted_at')`); `app/Http/Controllers/ReportController.php:96` | Números gerenciais inflados e divergentes do fluxo de caixa real (viola RN-14); recorrente sempre que houver exclusões ou cancelamentos | P | Confirmado |
| 10 | **Média** | Bug | Fluxo | Mensagens `flash` não são renderizadas no layout global e formulários usam data/hora em UTC | `resources/js/Layouts/AuthenticatedLayout.vue` não exibe `$page.props.flash` (só `resources/js/Pages/Backup/Index.vue:80` exibe `success`); `app/Http/Middleware/HandleInertiaRequests.php:37-40` omite `info`; `resources/js/Pages/Transactions/Create.vue:39` e `resources/js/Pages/Appointments/Create.vue:22` usam `toISOString()` | Usuário fica sem feedback de sucesso/erro após salvar/cancelar na maioria das telas, e após 21h (UTC-3) novos registros sugerem a data do dia seguinte | P | Confirmado |
| 11 | **Média** | Bug | Operação | Comandos de backup usam `env()` direto (quebram com `config:cache`), sem agendamento diário, e `deploy.sh` testa porta errada | `app/Console/Commands/BackupRun.php:45,65,88`, `app/Console/Commands/BackupRestore.php:18`, `docker/entrypoint.sh:21`, `routes/console.php`, `scripts/deploy.sh:28` vs `compose.prod.yaml:3-5` | Em produção (com `php artisan optimize`), `BACKUP_PATH` cai para `base_path('backups')` em vez do volume `/backups`, backups não rodam automaticamente (RNF-13) e o smoke test do deploy falha | P | Confirmado |
| 12 | **Média** | Bug | Docs | `.env.example` possui chaves `MAIL_*` duplicadas e `DB_DATABASE` apontando para caminho exclusivo de container | `.env.example:20` (`/data/db/database.sqlite`), `.env.example:35-52` | Quebra o onboarding local da Opção 1 do `README.md:134-139` e sobrescreve `MAIL_FROM_ADDRESS` (`contato@gestagil.app` → `hello@example.com`) e `MAIL_PORT` (`1025` → `2525`) | P | Confirmado |
| 13 | **Média** | Lacuna funcional | Operação | Ausência de pipeline de CI e de logs estruturados para eventos sensíveis | Sem `.github/workflows/`; `Log::` só existe em `BackupRun`/`BackupRestore` (nenhum log em auth, exclusão de conta ou emissão/cancelamento de recibos) | Regressões podem entrar na `master` sem verificação automática (RNF-11) e incidentes de segurança/auditoria não deixam rastro (RF-TRV-04) | P | Confirmado |
| 14 | **Média** | Lacuna funcional | Fluxo | Histórico do cliente, CRUD de categorias personalizadas e filtros secundários ausentes | `app/Http/Controllers/ClientController.php:52-57` e `resources/js/Pages/Clients/Show.vue` sem histórico/total (RF-CLI-05); sem rotas/UI de `TransactionCategory` (RF-CX-07); `resources/js/Pages/Transactions/Index.vue` sem filtro por categoria/meio/cliente (RF-CX-04); `resources/js/Pages/Appointments/Index.vue` sem filtro por período/cliente (RF-ATD-04); `resources/js/Pages/Appointments/Create.vue:63` sai da tela para cadastrar cliente (RF-ATD-06) | Reduz a produtividade diária no balcão e impede personalizar categorias ou consultar quanto cada cliente já gastou | M | Confirmado |
| 15 | **Baixa** | Bug | Fluxo | Textos em inglês/sem formatação na UI, campos de endereço ausentes no formulário da loja e aviso não-fiscal ausente no PDF | `payment_method` cru (`CASH`/`CARD`/`OTHER`) em `resources/js/Pages/Transactions/Index.vue:204`, `resources/js/Pages/Receipts/Show.vue:288`, `resources/js/Pages/Public/ReceiptVerification.vue:131`, `resources/js/Pages/Reports/Index.vue:272` e `resources/views/pdf/receipt.blade.php:291`; `"Log Out"` em `resources/js/Layouts/AuthenticatedLayout.vue:125,255`; `occurred_on` cru em `resources/js/Pages/Receipts/Create.vue:92`; anos fixos `2024–2027` em `resources/js/Pages/Reports/Index.vue:143-146`; `neighborhood`/`complement` ausentes em `resources/js/Pages/BusinessProfile/Edit.vue:257-281`; aviso não-fiscal ausente em `resources/views/pdf/receipt.blade.php:296-302` (RN-10) | Polimento visual, conformidade com pt-BR (RNF-16) e clareza legal do recibo; baixo risco operacional | P | Confirmado |
| 16 | **Baixa** | Melhoria | Operação | Relatório anual executa 24 queries em loop e `package.json` mistura Tailwind v3 e v4 | `app/Http/Controllers/ReportController.php:135-156`; `package.json:14,27` (`@tailwindcss/vite: ^4.0.0` + `tailwindcss: ^3.2.1`) | Overhead evitável no SQLite e risco de conflito em futuras atualizações de build frontend | P | Confirmado |

---

## Divergências Documentação × Código

- **Prometido na doc e não implementado:**
  - Rodapé customizado do recibo (`RF-NEG-02` / `README.md:72`) não existe na tabela `business_profiles` nem na UI.
  - Double check digitando `SOBRESCREVER` (`README.md:99`) — a UI (`resources/js/Pages/Backup/Index.vue:248-253`) usa apenas checkbox.
  - Cadastro inline de cliente no atendimento (`RF-ATD-06` / `README.md:51`) — redireciona para `clients.create`.
  - Edição de lançamentos (`RF-CX-06`), histórico completo do cliente (`RF-CLI-05`), lançamentos/recibos no detalhe do atendimento (`RF-ATD-07`) e CRUD de categorias (`RF-CX-07`).
  - Policies por modelo (`README.md:107` / `docs/02-requisitos.md:168`) — não há nenhuma classe em `app/Policies/`; o isolamento depende apenas do `UserScope`.
  - Arquivo `LICENSE` linkado em `README.md:199` não existe no repositório.
- **Documentação/stack desatualizada:**
  - `README.md:11,107-108` cita **Laravel 11** e **shadcn-vue**, mas `composer.json:12` usa **Laravel 13 (`^13.17`)** e `package.json` não possui `shadcn-vue`.
  - `docs/02-requisitos.md:174-179` lista rotas em português (`/clientes`, `/atendimentos`, `/caixa`, `/recibos`, `/configuracoes/*`), enquanto `routes/web.php` usa inglês (`/clients`, `/appointments`, `/transactions`, `/receipts`, `/business-profile`), exceto `/relatorios`, `/termos` e `/privacidade`.
  - Ranking de clientes em `app/Http/Controllers/ReportController.php:107-129` ordena por faturamento de transações, diferindo da RN-12 (≥ 2 atendimentos concluídos).

---

## Plano de Evolução (Resumo)

- **Fase 1: Correções críticas e fechamento de fluxos** (`#1` a `#8`) — Ver detalhamento passo a passo em `docs/implementation-plan.md`.
  - **#1:** Filtrar busca de recibos por `user_id` em `RestoreUserDataAction` e regenerar `code` em caso de colisão com outro tenant.
  - **#2:** Restringir validações `exists` por `user_id` (e `whereNull('deleted_at')`) em `AppointmentRequest`, `TransactionRequest` e `IssueReceiptRequest`.
  - **#3:** Alinhar disco do logo para `local` em `ReceiptPdfService` e `ProfileController::destroy`.
  - **#4:** Corrigir colunas exportadas de `BusinessProfile` em `ExportUserDataAction` e adicionar `withTrashed()` em `RestoreUserDataAction::clearUserData`.
  - **#5:** Bloquear exclusão de transação com recibo ativo (RN-22) e de atendimento com transações vinculadas (RF-ATD-05).
  - **#6:** Corrigir parse de `occurred_on` em `Transactions/Index.vue` e filtrar categorias por `form.type` em `Transactions/Create.vue` + backend.
  - **#7:** Validar perfil completo (RN-18) e recibo único dentro de `DB::transaction` (RN-08); validar RN-02, RN-03, RN-05 e RN-07 em `TransactionController`/`TransactionRequest`.
  - **#8:** Implementar edição de lançamentos (RF-CX-06) e conectar `Atendimento → Caixa` com botão de recebimento e lista de vínculos em `Appointments/Show.vue` (RF-ATD-07).
- **Fase 2: Experiência e produtividade** (`#9` a `#14`)
  - **#9:** Corrigir soft-deletes em `getClientsReport` e excluir cancelados de `expected_total_cents`.
  - **#10:** Renderizar `flash` (`success`/`error`/`info`) no `AuthenticatedLayout.vue` e corrigir datas locais (sem UTC) nos formulários.
  - **#11:** Criar `config/backup.php`, escapar path do `VACUUM INTO`, agendar `backup:run` em `routes/console.php` e corrigir porta em `scripts/deploy.sh`.
  - **#12:** Limpar `.env.example` (chaves `MAIL_*` duplicadas e `DB_DATABASE` local).
  - **#13:** Configurar `.github/workflows/ci.yml` e logs de auditoria em eventos sensíveis.
  - **#14:** Adicionar histórico do cliente, CRUD de categorias, filtros faltantes e cadastro rápido de cliente no atendimento.
- **Fase 3: Maturidade e escala** (`#15` e `#16`)
  - **#15:** Padronizar labels pt-BR, aviso não-fiscal no PDF, anos dinâmicos e campos de endereço na loja.
  - **#16:** Otimizar relatório anual para 1 query `GROUP BY`, remover `@tailwindcss/vite` conflitante, atualizar `README.md` e criar `LICENSE`.

---

## Pendências de Validação

Todos os **16 achados** desta auditoria possuem confiança **Confirmado** diretamente no código-fonte. Não há achados classificados como **Hipótese** ou **Provável**.

**Pendência de ambiente para validação em runtime:**
- **Execução da suíte Pest (`69` testes):** o ambiente PHP local onde a auditoria rodou não possui a extensão `pdo_sqlite` instalada. Antes de iniciar a Fase 1, confirmar que a suíte atual passa integralmente dentro do container Docker (`docker compose exec app php artisan test`) ou após habilitar `php-sqlite3` no host.
