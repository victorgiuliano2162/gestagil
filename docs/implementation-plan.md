# Plano de Implementação Passo a Passo — GestÁgil

> **Base:** `docs/gap-analysis.md` (commit `e06a4ac`) · **Data:** 22/11/2026

---

## 1. Visão Geral

**Objetivo:** corrigir os 7 bugs críticos de segurança, integridade de dados e fluxo e fechar as 2 lacunas centrais da **Fase 1** (`#1` a `#8`), mantendo 100% de aderência à stack atual (Laravel 13, Inertia 2, Vue 3, SQLite WAL, Pest) e sem introduzir novas dependências.

### Pré-requisitos globais
1. Criar uma branch dedicada a partir de `master` (ex.: `git checkout -b fix/phase-1-critical-gaps`).
2. Garantir ambiente com `pdo_sqlite` ativo (container Docker via `docker compose up -d` ou extensão `php-sqlite3` no host) para rodar `php artisan test`.
3. Rodar a suíte atual como baseline (`php artisan test`) e `npm run build` para confirmar build limpo antes de alterar código.
4. Nenhuma migration destrutiva é necessária na Fase 1 (não há alteração de schema obrigatória). Caso se opte futuramente por índice parcial único em `receipts`, fazer backup prévio com `php artisan backup:run`.

### Ordem de Execução (por dependência)

| Ordem | # | Tarefa | Esforço | Depende de |
| :---: | :---: | :--- | :---: | :--- |
| 1 | **#1** | Impedir sequestro/sobrescrita cross-tenant de recibos na restauração de backup | P | — |
| 2 | **#2** | Escopar validações `exists` de FKs por `user_id` nos FormRequests | P | — |
| 3 | **#3** | Corrigir disco do logotipo (`local`) em `ReceiptPdfService` e `ProfileController::destroy` | P | — |
| 4 | **#4** | Corrigir exportação de `BusinessProfile` e limpeza de soft-deletes no modo `replace` | P | #1 (mesmo arquivo) |
| 5 | **#5** | Bloquear exclusão de lançamento com recibo ativo e atendimento com lançamentos | P | — |
| 6 | **#6** | Corrigir `Invalid Date` no caixa e categorias reativas ao alternar Entrada/Saída | P | #2 |
| 7 | **#7** | Aplicar regras RN-18/RN-08 em recibos e RN-02/03/05/07 em lançamentos | P | #2, #5 |
| 8 | **#8** | Implementar edição de lançamentos (RN-22) e fechar fluxo `Atendimento → Caixa` na UI | M | #5, #6, #7 |

---

## 2. Detalhamento da Fase 1

### Tarefa 1 — #1: Restauração de backup permite sequestrar recibo de outro usuário por `code`

- **Achado:** `#1` — Restauração de backup permite sequestrar recibo de outro usuário por `code` (IDOR/overwrite cross-tenant).
- **Objetivo:** Garantir que a restauração de recibos nunca leia nem atualize recibos pertencentes a outro usuário.
- **Pré-condições:** Nenhuma.
- **Passos:**
  1. Em `app/Actions/Backup/RestoreUserDataAction.php:250-252`, filtrar a consulta do recibo existente pelo usuário atual:
     ```php
     $receipt = Receipt::withoutGlobalScopes()
         ->where('user_id', $user->id)
         ->where('code', $code)
         ->first();
     ```
  2. No ramo `else` (`app/Actions/Backup/RestoreUserDataAction.php:274-293`), antes de criar o recibo com `$code`, verificar se `$code` já pertence a outro usuário (`Receipt::withoutGlobalScopes()->where('code', $code)->exists()`). Se existir, gerar novo UUID com `(string) Str::uuid()` para evitar violação da constraint `unique('code')` sem revelar a existência do recibo alheio.
- **Testes:** Em `tests/Feature/UserBackupTest.php`, adicionar caso onde o Usuário B tenta restaurar (em modo `merge` e `replace`) um JSON contendo o `code` de um recibo do Usuário A; validar que o recibo do Usuário A permanece inalterado (`user_id`, `payer_name`, `amount_cents`) e que o recibo importado pelo Usuário B recebe um novo `code`.
- **Como validar:** `php artisan test --compact tests/Feature/UserBackupTest.php`.
- **Risco e rollback:** Risco mínimo — apenas restringe o escopo de escrita ao dono da sessão. Rollback via `git checkout -- app/Actions/Backup/RestoreUserDataAction.php`.
- **Critério de pronto:** Nenhum usuário consegue alterar ou tomar posse de recibo de outro tenant via upload de backup; teste de regressão passando.
- **Esforço:** P.

---

### Tarefa 2 — #2: Validações `exists` de chaves estrangeiras não filtram por `user_id`

- **Achado:** `#2` — Validações `exists` de chaves estrangeiras não filtram por `user_id`.
- **Objetivo:** Impedir que qualquer requisição de criação/edição associe `client_id`, `appointment_id`, `category_id` ou `transaction_id` pertencente a outro usuário.
- **Pré-condições:** Nenhuma.
- **Passos:**
  1. Em `app/Http/Requests/AppointmentRequest.php:34`, substituir `'exists:clients,id'` por:
     ```php
     Rule::exists('clients', 'id')
         ->where('user_id', $this->user()->id)
         ->whereNull('deleted_at'),
     ```
  2. Em `app/Http/Requests/TransactionRequest.php:38-41`, substituir `exists` de `category_id`, `client_id` e `appointment_id` por `Rule::exists(...)->where('user_id', $this->user()->id)` (com `->whereNull('deleted_at')` em `clients` e `appointments`).
  3. Em `app/Http/Requests/IssueReceiptRequest.php:17`, substituir `'exists:transactions,id'` por `Rule::exists('transactions', 'id')->where('user_id', $this->user()->id)->whereNull('deleted_at')`.
  4. Como defesa em profundidade, em `app/Http/Controllers/ReportController.php:57`, adicionar filtro `->where('transaction_categories.user_id', $userId)` na cláusula do `leftJoin` (via closure `$join->on(...)->where(...)`).
- **Testes:** Adicionar testes de erro (HTTP 422 / `assertSessionHasErrors`) em `AppointmentTest.php`, `TransactionTest.php` e `ReceiptTest.php` tentando enviar IDs pertencentes a outro usuário nos campos `client_id`, `appointment_id`, `category_id` e `transaction_id`.
- **Como validar:** `php artisan test --compact tests/Feature/AppointmentTest.php tests/Feature/TransactionTest.php tests/Feature/ReceiptTest.php`.
- **Risco e rollback:** Baixo; pode rejeitar payloads antigos caso algum teste crie modelos sem `user_id` consistente. Rollback revertendo os 3 FormRequests e `ReportController.php`.
- **Critério de pronto:** Todas as tentativas de referência cruzada entre tenants falham na validação (422) sem gravar no banco.
- **Esforço:** P.

---

### Tarefa 3 — #3: Logo da loja nunca aparece no PDF e não é removido na exclusão de conta (LGPD)

- **Achado:** `#3` — Logo da loja nunca aparece no PDF do recibo e não é removido na exclusão de conta (LGPD) por divergência de disco (`local` × `public`).
- **Objetivo:** Fazer `ReceiptPdfService` embutir o logotipo salvo no disco `local` e `ProfileController::destroy` apagar o arquivo físico corretamente ao encerrar a conta.
- **Pré-condições:** Nenhuma.
- **Passos:**
  1. Em `app/Services/ReceiptPdfService.php:17-20`, trocar `Storage::disk('public')` por `Storage::disk('local')` (mesmo disco usado em `app/Http/Controllers/BusinessProfileController.php:50`).
  2. Em `app/Http/Controllers/ProfileController.php:73`, trocar `Storage::disk('public')->delete(...)` por `Storage::disk('local')->delete(...)`.
  3. Verificar se `tests/Feature/ReceiptTest.php` ou outros testes criam logos falsos no disco `public`; se houver, atualizar para `Storage::fake('local')`.
- **Testes:**
  - Em `tests/Feature/ReceiptTest.php`, adicionar teste com `Storage::fake('local')` + logo salvo via `BusinessProfileController` verificando que `ReceiptPdfService::render` recebe `$logoBase64` preenchido (ou gera PDF sem erro) e outro caso de borda quando `logo_path` aponta para arquivo inexistente (deve renderizar sem exceção).
  - Em `tests/Feature/ProfileTest.php`, adicionar teste verificando que após `DELETE /profile` o arquivo em `Storage::disk('local')` é removido (`assertMissing`).
- **Como validar:** `php artisan test --compact tests/Feature/ReceiptTest.php tests/Feature/ProfileTest.php tests/Feature/BusinessProfileTest.php`.
- **Risco e rollback:** Risco mínimo. Caso existam logos antigos já gravados em `public` em alguma instalação prévia, não há quebra (apenas não exibirá até novo upload). Rollback revertendo os 2 arquivos.
- **Critério de pronto:** PDF inclui a imagem em base64 quando há logo no disco `local` e a exclusão de conta remove o arquivo físico do disco `local`.
- **Esforço:** P.

---

### Tarefa 4 — #4: Exportação de backup de `BusinessProfile` quebrada e `replace` sem limpar soft-deletes

- **Achado:** `#4` — Exportação de backup acessa 7 colunas inexistentes em `BusinessProfile` e descarta 5 reais; `replace` não limpa soft-deletes.
- **Objetivo:** Exportar e restaurar todas as colunas reais de `BusinessProfile` sem lançar `MissingAttributeException` e garantir que o modo `replace` limpe também registros soft-deleted.
- **Pré-condições:** Tarefa `#1` concluída (ambas alteram `RestoreUserDataAction.php`).
- **Passos:**
  1. Em `app/Actions/Backup/ExportUserDataAction.php:134-150`, substituir o array `business_profile` pelas colunas reais do schema (`display_name`, `store_name`, `activity_description`, `document_type`, `document_number`, `phone`, `postal_code`, `street`, `number`, `complement`, `neighborhood`, `city`, `state`). Não exportar `logo_path` (caminho local de filesystem que não existe em outra máquina).
  2. Em `app/Actions/Backup/RestoreUserDataAction.php:51-56`, filtrar `$data['business_profile']` com `Arr::only(..., [...])` antes de chamar `updateOrCreate`, garantindo compatibilidade retroativa com JSONs antigos e evitando mass-assignment de colunas inexistentes.
  3. Em `app/Actions/Backup/RestoreUserDataAction.php:319-325`, adicionar `->withTrashed()` antes de `->where('user_id', $user->id)->forceDelete()` em `Transaction`, `Appointment` e `Client`.
- **Testes:** Em `tests/Feature/UserBackupTest.php`:
  - Criar `BusinessProfile` completo (com `street`, `number`, `complement`, `neighborhood`, `activity_description`) no teste de exportação e validar que `GET /backup/export` retorna 200 (com `Model::shouldBeStrict()` ativo) e preserva todos os campos no round-trip `export → replace`.
  - Adicionar caso de borda: soft-deletar um cliente/transação, restaurar em modo `replace` um backup contendo o mesmo `uuid` e confirmar sucesso sem erro de `UNIQUE(uuid)`.
- **Como validar:** `php artisan test --compact tests/Feature/UserBackupTest.php`.
- **Risco e rollback:** Baixo; manter `Arr::only` no restore protege contra backups antigos que contenham chaves legadas. Rollback revertendo os 2 arquivos em `app/Actions/Backup/`.
- **Critério de pronto:** Exportação funciona para usuários com `BusinessProfile` em modo strict, todos os dados da loja sobrevivem ao round-trip e `replace` não falha com registros soft-deleted.
- **Esforço:** P.

---

### Tarefa 5 — #5: Exclusão de lançamento com recibo ativo e atendimento com vínculos não é bloqueada

- **Achado:** `#5` — Exclusão de lançamento com recibo ativo e de atendimento com vínculos não é bloqueada.
- **Objetivo:** Fazer cumprir RN-22 (não excluir lançamento com recibo ativo) e RF-ATD-05 (não excluir atendimento com lançamentos vinculados), além de corrigir a relação de recibos no model `Transaction`.
- **Pré-condições:** Nenhuma.
- **Passos:**
  1. Em `app/Models/Transaction.php:56-59`, adicionar `receipts(): HasMany` e ajustar/adicionar `activeReceipt(): HasOne` com `->where('status', 'issued')` (mantendo `receipt()` apontando para `latestOfMany()` ou `activeReceipt()` conforme uso existente — verificar todas as chamadas a `->receipt` via `grep`).
  2. Em `app/Models/Appointment.php`, adicionar a relação `transactions(): HasMany`.
  3. Em `app/Http/Controllers/TransactionController.php:119-125`, antes de `$transaction->delete()`, checar `$transaction->receipts()->where('status', 'issued')->exists()`. Se verdadeiro, retornar `back()->with('error', 'Não é possível excluir um lançamento com recibo ativo. Cancele o recibo antes.')`.
  4. Em `app/Http/Controllers/AppointmentController.php:149-155`, antes de `$appointment->delete()`, checar `$appointment->transactions()->exists()`. Se verdadeiro, retornar `back()->with('error', 'Não é possível excluir um atendimento que possui lançamentos vinculados.')`.
  5. Em `app/Http/Controllers/TransactionController.php:27-30`, carregar `withExists(['receipts as has_active_receipt' => fn ($q) => $q->where('status', 'issued')])` para que `resources/js/Pages/Transactions/Index.vue:218-223` desabilite ou oculte o botão "Excluir" quando `t.has_active_receipt` for verdadeiro (com tooltip explicativo).
- **Testes:**
  - Em `tests/Feature/TransactionTest.php`: tentar excluir receita com recibo `issued` (deve ser bloqueado e manter registro no banco); cancelar o recibo e tentar novamente (deve permitir exclusão).
  - Em `tests/Feature/AppointmentTest.php`: tentar excluir atendimento com transação vinculada (deve ser bloqueado); excluir atendimento sem vínculos (deve permitir).
- **Como validar:** `php artisan test --compact tests/Feature/TransactionTest.php tests/Feature/AppointmentTest.php tests/Feature/ReceiptTest.php`.
- **Risco e rollback:** Baixo; altera apenas as guardas de `destroy`. Rollback revertendo os models, controllers e `Transactions/Index.vue`.
- **Critério de pronto:** Lançamentos com recibo ativo e atendimentos com lançamentos vinculados não podem ser excluídos via UI nem via requisição direta.
- **Esforço:** P.

---

### Tarefa 6 — #6: `Invalid Date` na listagem do caixa e categorias desatualizadas ao alternar tipo

- **Achado:** `#6` — Data exibe `Invalid Date` na listagem do caixa e alternar Entrada/Saída não atualiza categorias.
- **Objetivo:** Exibir corretamente as datas em `pt-BR` na listagem do caixa e garantir que apenas categorias compatíveis com o tipo (`income`/`expense`) sejam selecionáveis e aceitas no backend.
- **Pré-condições:** Tarefa `#2` concluída (reutiliza a validação de `category_id` em `TransactionRequest`).
- **Passos:**
  1. Em `resources/js/Pages/Transactions/Index.vue:189`, extrair apenas os 10 primeiros caracteres (`YYYY-MM-DD`) antes de formatar:
     ```ts
     const formatDateOnly = (iso: string) => {
         const [y, m, d] = iso.slice(0, 10).split('-');
         return `${d}/${m}/${y}`;
     };
     ```
  2. Em `app/Http/Controllers/TransactionController.php:66`, buscar todas as categorias do usuário (`id`, `name`, `color`, `type`) em vez de filtrar apenas pelo `$type` inicial.
  3. Em `resources/js/Pages/Transactions/Create.vue:26-55`, filtrar `categories` via `computed(() => props.categories.filter(c => c.type === form.type))` e adicionar `watch(() => form.type, () => { form.category_id = ''; })` para limpar a seleção ao alternar entre Entrada e Saída.
  4. Em `app/Http/Requests/TransactionRequest.php:38`, adicionar `->where('type', $this->input('type'))` na regra `Rule::exists('transaction_categories', 'id')` (garantindo RN-20 no servidor).
- **Testes:** Em `tests/Feature/TransactionTest.php`, adicionar teste tentando salvar `expense` com uma categoria de `income` (deve falhar com erro de validação em `category_id`) e verificar na prop Inertia de `transactions.create` que categorias de ambos os tipos são entregues.
- **Como validar:** `php artisan test --compact tests/Feature/TransactionTest.php && npm run build`; abrir `/transactions` e `/transactions/create` no navegador, conferir data formatada (`dd/mm/aaaa`) e alternar Entrada ↔ Saída observando a troca do select de categorias.
- **Risco e rollback:** Baixo. Rollback revertendo `TransactionController.php`, `TransactionRequest.php`, `Transactions/Index.vue` e `Transactions/Create.vue`.
- **Critério de pronto:** Nenhuma data aparece como `Invalid Date` no caixa e não é possível gravar lançamento com categoria de tipo divergente.
- **Esforço:** P.

---

### Tarefa 7 — #7: Regras de negócio críticas ausentes em recibos e lançamentos

- **Achado:** `#7` — Regras de negócio críticas ausentes na emissão de recibo e criação de lançamentos (RN-02, RN-03, RN-05, RN-07, RN-08, RN-18).
- **Objetivo:** Garantir atomicidade na unicidade de recibo ativo, exigir perfil comercial completo na emissão e validar integridade de data, teto monetário e vínculo com atendimento nos lançamentos.
- **Pré-condições:** Tarefas `#2` e `#5` concluídas.
- **Passos:**
  1. Em `app/Http/Controllers/ReceiptController.php:53-155`:
     - Em `create` e `store`, validar RN-18: se `$profile` não existir ou `empty($profile->display_name)` ou `empty($profile->document_number)`, redirecionar para `route('business-profile.edit')` com `error` informando que nome/razão e CPF/CNPJ são obrigatórios para emitir recibos.
     - Em `store`, mover a verificação de `$activeReceipt` (`Receipt::where('transaction_id', $transaction->id)->where('status', 'issued')->lockForUpdate()->first()`) para **dentro** do `DB::transaction(...)` (que em SQLite WAL já roda com `BEGIN IMMEDIATE`), eliminando a condição de corrida (RN-08).
  2. Em `app/Http/Requests/TransactionRequest.php:34-43`:
     - Adicionar `'max:9999999.99'` na regra de `amount` (RN-02).
     - Adicionar `'before_or_equal:today'` na regra de `occurred_on` (RN-03) com mensagem clara em `messages()`.
  3. Em `app/Http/Controllers/TransactionController.php:99-117`:
     - Se `appointment_id` estiver presente, carregar `$appt = Appointment::findOrFail($data['appointment_id'])`:
       - Se `$appt->status === AppointmentStatus::Canceled`, retornar erro de validação em `appointment_id` (RN-05).
       - Sempre sobrescrever `$data['client_id'] = $appt->client_id` (RN-07 — impede divergência mesmo quando `client_id` vem preenchido).
- **Testes:**
  - `tests/Feature/ReceiptTest.php`: tentar emitir recibo sem `document_number` no `BusinessProfile` (bloqueado com redirect para `business-profile.edit`); verificar que testes existentes de emissão tenham `document_number` preenchido no setup.
  - `tests/Feature/TransactionTest.php`: testar data futura (422), valor acima de `9999999.99` (422), vínculo com atendimento cancelado (422) e vínculo com atendimento informando outro `client_id` (deve gravar o `client_id` do atendimento).
- **Como validar:** `php artisan test --compact tests/Feature/ReceiptTest.php tests/Feature/TransactionTest.php tests/Feature/EndToEndJourneyTest.php`.
- **Risco e rollback:** **Atenção:** exigir `document_number` (RN-18) pode quebrar testes legados em `ReceiptTest.php` ou `EndToEndJourneyTest.php` que não preenchiam `document_number` na factory/setup — ajustar esses setups no mesmo commit. Rollback revertendo `ReceiptController.php`, `TransactionController.php` e `TransactionRequest.php`.
- **Critério de pronto:** Todas as 6 regras (RN-02, RN-03, RN-05, RN-07, RN-08, RN-18) estão protegidas no backend e cobertas por testes automatizados.
- **Esforço:** P.

---

### Tarefa 8 — #8: Edição de lançamentos (RN-22) e fechamento do fluxo `Atendimento → Caixa`

- **Achado:** `#8` — Fluxo principal `Atendimento → Caixa` desconectado na UI e CRUD de lançamentos sem edição.
- **Objetivo:** Permitir editar lançamentos respeitando o bloqueio quando há recibo ativo (RN-22) e conectar a tela de detalhe do atendimento ao registro de recebimento e à visualização de lançamentos/recibos vinculados (RF-ATD-07).
- **Pré-condições:** Tarefas `#5`, `#6` e `#7` concluídas.
- **Passos:**
  1. **Edição de lançamentos (backend):**
     - Em `routes/web.php:58`, expandir `Route::resource('transactions', TransactionController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])`.
     - Em `app/Http/Controllers/TransactionController.php`, implementar `edit(Transaction $transaction)` (passando `transaction`, `categories` de ambos os tipos, `clients`, `paymentMethods` e flag `hasActiveReceipt`) e `update(TransactionRequest $request, Transaction $transaction)`: se houver recibo ativo (`status = 'issued'`), bloquear alterações em `type`, `amount_cents`, `occurred_on`, `payment_method` e `description` (RN-22), permitindo atualizar apenas `category_id` e `notes` (ou redirecionando com erro claro).
  2. **Edição de lançamentos (frontend):**
     - Criar `resources/js/Pages/Transactions/Edit.vue` seguindo a estrutura de `Create.vue`, desabilitando os campos bloqueados com aviso visual quando `hasActiveReceipt` for verdadeiro.
     - Em `resources/js/Pages/Transactions/Index.vue:210-224`, adicionar link `Editar` (`route('transactions.edit', t.id)`).
  3. **Fluxo `Atendimento → Caixa` (backend + frontend):**
     - Em `app/Http/Controllers/AppointmentController.php:76-87`, carregar `$appointment->load(['client', 'transactions.category', 'transactions.activeReceipt'])` e enviar `transactions` para `Appointments/Show.vue`.
     - Em `resources/js/Pages/Appointments/Show.vue:108-140`, exibir botão `+ Registrar Recebimento` apontando para `route('transactions.create', { type: 'income', appointment_id: appointment.id })` quando `appointment.status !== 'canceled'`.
     - Em `resources/js/Pages/Appointments/Show.vue`, adicionar card "Lançamentos e Recibos Vinculados" listando as transações do atendimento com valor, data, link para emitir/ver recibo (`receipts.show` ou `receipts.create`) e estado vazio quando não houver lançamentos.
- **Testes:**
  - `tests/Feature/TransactionTest.php`: editar lançamento sem recibo (sucesso); tentar alterar valor/data/descrição de lançamento com recibo ativo (bloqueado — RN-22); cancelar recibo e editar (sucesso); isolamento cross-tenant em `edit`/`update` (404).
  - `tests/Feature/AppointmentTest.php`: verificar que `appointments.show` entrega os lançamentos e recibos vinculados na resposta Inertia.
- **Como validar:** `php artisan test --compact tests/Feature/TransactionTest.php tests/Feature/AppointmentTest.php tests/Feature/EndToEndJourneyTest.php && npm run build`; testar manualmente na UI a jornada `Atendimento → Concluir → + Registrar Recebimento → Emitir Recibo → tentar editar lançamento`.
- **Risco e rollback:** Médio (envolve nova tela Vue e novas rotas). Rollback removendo `edit`/`update` de `routes/web.php` e `TransactionController.php`, removendo `Transactions/Edit.vue` e revertendo `Appointments/Show.vue` e `AppointmentController.php`.
- **Critério de pronto:** Usuário consegue editar lançamentos (com proteção RN-22) e concluir toda a jornada `Atendimento → Recebimento → Recibo` sem sair do fluxo guiado.
- **Esforço:** M.

---

## 3. Fases 2 e 3 (Checklist Resumida)

> Quando quiser executar a Fase 2 ou a Fase 3, peça para detalhar os itens abaixo no mesmo formato passo a passo.

### Fase 2: Experiência e produtividade
- [ ] **#9 — Corrigir métricas de relatórios:** adicionar `whereNull('transactions.deleted_at')` em `ReportController::getClientsReport` e excluir atendimentos `canceled` de `expected_total_cents`.
- [ ] **#10 — Feedback global (`flash`) e datas locais:** compartilhar `flash.info` em `HandleInertiaRequests`, exibir banners de `flash` em `AuthenticatedLayout.vue` e criar helper de data local (`America/Recife`) para formulários.
- [ ] **#11 — Confiabilidade de backup e deploy:** mover `BACKUP_PATH`/`BACKUP_KEEP_DAYS` para `config/backup.php`, escapar path em `VACUUM INTO`, agendar `backup:run` diário em `routes/console.php` e corrigir porta do healthcheck em `scripts/deploy.sh`.
- [ ] **#12 — Corrigir `.env.example`:** remover chaves `MAIL_*` duplicadas e alinhar `DB_DATABASE` ao uso local (mantendo override de container em `compose.yaml`).
- [ ] **#13 — CI e logs de auditoria:** criar `.github/workflows/ci.yml` (`pint --test`, `php artisan test`, `npm run build`) e adicionar `Log::info` em login, exclusão de conta e emissão/cancelamento de recibos.
- [ ] **#14 — Completude operacional (histórico, categorias e filtros):** exibir histórico/total em `Clients/Show.vue`, criar CRUD enxuto de `TransactionCategory`, adicionar filtros faltantes no caixa/atendimentos e modal de cliente rápido em `Appointments/Create.vue`.

### Fase 3: Maturidade e escala
- [ ] **#15 — Polimento pt-BR, endereço e aviso não-fiscal:** mapear labels pt-BR de `PaymentMethod` em toda UI e PDF, traduzir `"Log Out"`, formatar `occurred_on` em `Receipts/Create.vue`, gerar anos dinamicamente em `Reports/Index.vue`, incluir `neighborhood`/`complement` em `BusinessProfile/Edit.vue` e inserir aviso de documento não fiscal em `receipt.blade.php`.
- [ ] **#16 — Performance e higiene de build/docs:** consolidar `getAnnualReport` em uma query `GROUP BY`, remover `@tailwindcss/vite` conflitante do `package.json`, atualizar `README.md` (Laravel 13, stack real) e adicionar `LICENSE`.

---

## 4. Checklist Final da Fase 1

- [ ] Todos os itens `#1` a `#8` implementados e revisados.
- [ ] Todos os novos testes de regressão (segurança cross-tenant, disco do logo, backup strict, bloqueios RN-22/RF-ATD-05, RN-02/03/05/07/08/18, edição de lançamentos e vínculo atendimento → caixa) escritos e passando.
- [ ] Suíte completa executada sem falhas: `php artisan test`.
- [ ] Formatação PHP validada: `vendor/bin/pint --dirty --format agent`.
- [ ] Build e checagem de tipos TypeScript/Vue sem erros: `npm run build`.
- [ ] Documentação afetada revisada (sem divergências novas introduzidas).
