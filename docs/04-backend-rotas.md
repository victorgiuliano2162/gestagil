# 04 — Backend: Rotas, Controllers e Regras (Laravel + Inertia)

> **Versão:** 0.3 (revisão enxuta: Inertia + SQLite, sem filas ou tabelas auxiliares desnecessárias) · **Data:** 06/11/2026 · Depende de: `01-arquitetura.md`, `02-requisitos.md`, `03-modelo-dados.md`
> **Não existe API REST.** Todas as interações são rotas web que respondem com páginas Inertia, redirecionamentos (`303`) ou download direto de PDF.

## 1. Organização do projeto

Estrutura padrão do Laravel, criada a partir do starter kit Vue:

```
gestagil/
├── app/
│   ├── Actions/            IssueReceipt, CancelReceipt, RegisterTransaction,
│   │                       ChangeAppointmentStatus, DeleteAccount
│   ├── Console/Commands/   BackupRun
│   ├── Enums/              AppointmentStatus, TransactionType, PaymentMethod, ReceiptStatus
│   ├── Http/
│   │   ├── Controllers/    ClientController, AppointmentController, TransactionController,
│   │   │                   ReceiptController, PublicReceiptController, DashboardController,
│   │   │                   ReportController, Settings/{BusinessProfile,Account,Category}Controller
│   │   ├── Middleware/     HandleInertiaRequests, EnsureBusinessProfile
│   │   └── Requests/       Um FormRequest por operação de escrita
│   ├── Mail/               ReceiptMail
│   ├── Models/ + Concerns/ (BelongsToUser, HasSearchName)
│   ├── Policies/
│   ├── Queries/            RevenueReport, AppointmentsReport, TopClientsReport, AnnualReport, DashboardSummary
│   └── Support/            Money, PhoneNumber, AmountInWords, CpfCnpj, TextNormalizer
├── config/ · database/{migrations,factories,seeders} · lang/pt_BR
├── resources/{js (Vue), views/{app.blade.php, pdf/receipt.blade.php, emails}}
├── routes/{web.php, settings.php, auth.php, console.php}
└── tests/{Feature,Unit}
```

## 2. Áreas funcionais

| Área | Responsabilidade | Classes principais |
| :--- | :--- | :--- |
| **Conta e acesso** | Cadastro (semeia categorias e cria perfil vazio), login, logout, recuperação de senha, edição e exclusão da conta | Controllers do starter kit + `Settings\AccountController`, `DeleteAccount` |
| **Negócio (emitente)** | Dados do emitente e logo | `Settings\BusinessProfileController`, `UpdateBusinessProfileRequest` |
| **Clientes** | CRUD, busca sem acento, resumo | `ClientController`, `ClientPolicy` |
| **Atendimentos** | CRUD, filtros, transições de status | `AppointmentController`, `ChangeAppointmentStatus`, `AppointmentPolicy` |
| **Fluxo de caixa** | Lançamentos, categorias, resumo do período | `TransactionController`, `Settings\CategoryController`, `TransactionPolicy`, `RegisterTransaction` |
| **Recibos** | Emissão, PDF sob demanda, numeração, cancelamento, compartilhamento, e-mail | `ReceiptController`, `IssueReceipt`, `CancelReceipt`, `PublicReceiptController` |
| **Relatórios** | Dashboard e relatórios agregados | `DashboardController`, `ReportController`, Query classes |
| **Plataforma** | Saúde (`/up`), logs em `stderr`, **backup (`backup:run`)** | `routes/console.php`, `BackupRun` |

## 3. Autenticação e autorização

### 3.1 Autenticação (sessão do Laravel, via starter kit)

| Item | Definição |
| :--- | :--- |
| Modelo | Sessão em arquivo (`SESSION_DRIVER=file`); cookie `HttpOnly`; CSRF tratado automaticamente pelo Inertia |
| URLs | Telas de autenticação mantêm as URLs do starter kit (`/login`, `/register`, `/forgot-password`, `/reset-password/{token}`); telas do sistema usam URLs em pt-BR (ver §5) |
| Senha | Hash forte (bcrypt/argon2); mínimo 8 caracteres |
| Proxy | `trustProxies` configurado se houver proxy/túnel na frente, para reconhecer HTTPS |
| Sessão | Inatividade 120 min; "manter conectado" 30 dias; regeneração de ID no login; invalidação no logout |
| Recuperação | *Password broker* nativo; token com expiração de 60 min; resposta neutra |
| Recursos do kit não usados | 2FA, verificação obrigatória de e-mail e login social: desativados no MVP |

### 3.2 Autorização

- **Isolamento:** trait `BelongsToUser` (*global scope* por `auth()->id()` + preenchimento automático de `user_id` na criação); *route model binding* passa pelo escopo, então recurso de terceiros gera **404**.
- **Policies** por modelo (`view`, `update`, `delete`, ações específicas como `cancel` e `issueReceipt`), chamadas nos Form Requests e Controllers.
- **Testes obrigatórios:** para cada recurso, usuário B não lê/edita/exclui dados do usuário A (IDOR).

### 3.3 Limites de requisição (*rate limiting*)

O limitador do Laravel usa o cache em `file`.

| Alvo | Limite |
| :--- | :--- |
| `POST /login` | 5/min por e-mail+IP (padrão do kit) |
| `POST /register` | 10/hora por IP |
| `POST /forgot-password` | 5/hora por IP e por e-mail |
| `POST /recibos/{receipt}/email` | 10/hora por usuário |
| `GET /r/{code}` e `/r/{code}/pdf` | 30/min por IP |

## 4. Padrões da camada web (Inertia)

| Tema | Padrão |
| :--- | :--- |
| Leitura | `GET` → `Inertia::render('Pasta/Pagina', props)`; props são arrays/Resources já no formato que a tela usa |
| Escrita | `POST/PUT/PATCH/DELETE` → FormRequest → Action/Model → `redirect()` (código 303) com mensagem *flash* (`success`/`error`) |
| Erros de validação | `422` automático: o Inertia devolve `errors` por campo ao `useForm` da página (mensagens em `pt_BR`) |
| Mensagens | *Flash* compartilhado por `HandleInertiaRequests` e exibido como *toast* |
| Listas e Comboboxes | Paginação do Laravel com `withQueryString()` nas listagens. Para formulários (seleção de cliente ou atendimento), a lista enxuta (`id`, `name`, `phone`) do usuário é enviada diretamente como prop Inertia da página (dispensando endpoints JSON extras) |
| Downloads | PDF por rota comum (`Content-Disposition`), fora do fluxo Inertia (link `<a>`/`fetch`, nunca `router.visit`) |
| Datas | Datas `YYYY-MM-DD`; instantes em ISO 8601 local (`America/Recife`) |
| Dinheiro | Sempre `*_cents` (inteiro) nas props; formatação no navegador |
| Enums | Valores em inglês (`in_progress`, `pix`); rótulos em pt-BR nas páginas |
| Idioma | `APP_LOCALE=pt_BR`, `lang/pt_BR` para validação e textos do sistema |
| Saúde | `/up` (liveness nativo do Laravel) |

## 5. Rotas

Legenda: 🔒 exige sessão · 🌐 público. "Tela" remete às telas de `05`. Nomes de rota em inglês (`clients.index`); URLs de tela em pt-BR.

### 5.1 Plataforma e autenticação

| Método | URL | Descrição | Tela | Acesso |
| :--- | :--- | :--- | :---: | :---: |
| GET | `/up` | Liveness | — | 🌐 |
| GET | `/` | Redireciona para `/dashboard` (logado) ou `/login` | — | 🌐 |
| GET / POST | `/login` | Formulário e autenticação (*throttle*) | S-01 | 🌐 |
| POST | `/logout` | Encerra a sessão | — | 🔒 |
| GET / POST | `/register` | Cadastro (exige aceite de termos); semeia categorias padrão e cria `business_profile` vazio em transação; autentica | S-02 | 🌐 |
| GET / POST | `/forgot-password` | Solicita link de redefinição (resposta neutra) | S-03 | 🌐 |
| GET / POST | `/reset-password/{token}` | Redefine a senha | S-04 | 🌐 |

### 5.2 Configurações e conta

| Método | URL | Descrição | Tela | Acesso |
| :--- | :--- | :--- | :---: | :---: |
| GET / PUT | `/configuracoes/negocio` | Lê/atualiza dados do emitente | S-19 | 🔒 |
| POST / DELETE | `/configuracoes/negocio/logo` | Envia/remove logo | S-19 | 🔒 |
| GET / PUT | `/configuracoes/conta` | Lê/atualiza nome e e-mail | S-20 | 🔒 |
| PUT | `/configuracoes/senha` | Altera a senha (exige a atual) | S-20 | 🔒 |
| DELETE | `/configuracoes/conta` | Exclui conta e todos os dados em ordem reversa (exige senha) | S-20 | 🔒 |
| GET | `/configuracoes/categorias` | Lista de categorias | S-21 | 🔒 |
| POST / PUT / DELETE | `/configuracoes/categorias[/{category}]` | Cria, renomeia, arquiva/exclui | S-21 | 🔒 |

`GET /configuracoes` redireciona para `/configuracoes/negocio`. Todas as ações de conta e exclusão LGPD ficam unificadas em `Settings\AccountController` (S-20).

### 5.3 Clientes

| Método | URL | Descrição | Tela | Notas |
| :--- | :--- | :--- | :---: | :--- |
| GET | `/clientes` | Lista paginada | S-12 | `q` (nome sem acento/telefone por dígitos), `sort`, `page` |
| GET | `/clientes/novo` | Formulário | S-13 | |
| POST | `/clientes` | Cria | S-13 | Suporta criação inline no atendimento; avisa se telefone já existe (**PV-29**) |
| GET | `/clientes/{client}` | Detalhe (props: resumo, atendimentos e recibos recentes) | S-14 | |
| GET | `/clientes/{client}/editar` | Formulário de edição | S-13 | |
| PUT | `/clientes/{client}` | Atualiza | S-13 | |
| DELETE | `/clientes/{client}` | Exclusão lógica (soft delete) | S-12/S-14 | |

### 5.4 Atendimentos

| Método | URL | Descrição | Tela | Notas |
| :--- | :--- | :--- | :---: | :--- |
| GET | `/atendimentos` | Lista paginada | S-07 | `status`, `cliente`, `de`, `ate`, `q`, `sort` |
| GET | `/atendimentos/novo` | Formulário | S-08 | Aceita `?cliente=`; envia clientes ativos nas props |
| POST | `/atendimentos` | Cria | S-08 | |
| GET | `/atendimentos/{appointment}` | Detalhe (props: cliente, lançamentos e recibos vinculados) | S-09 | |
| GET | `/atendimentos/{appointment}/editar` | Formulário de edição | S-08 | Atualiza dados (exceto transição de status) |
| PUT | `/atendimentos/{appointment}` | Atualiza | S-08 | |
| PATCH | `/atendimentos/{appointment}/status` | Muda status via `ChangeAppointmentStatus` | S-07/S-09 | Body: `status`, `cancel_reason?` (RN-04) |
| DELETE | `/atendimentos/{appointment}` | Exclusão lógica | S-09 | Bloqueada se houver lançamentos/recibos |

### 5.5 Fluxo de caixa

| Método | URL | Descrição | Tela | Notas |
| :--- | :--- | :--- | :---: | :--- |
| GET | `/caixa` | Lista + resumo do período | S-10 | `tipo`, `de`, `ate`, `categoria`, `forma`, `cliente`, `q`, `page` |
| GET | `/caixa/novo` | Formulário | S-11 | `?tipo=income\|expense&atendimento=`; categorias, clientes e atendimentos nas props |
| POST | `/caixa` | Cria receita/despesa | S-11 | RN-02, RN-03, RN-07; opção "salvar e emitir recibo" |
| GET | `/caixa/{transaction}/editar` | Formulário de edição | S-11 | Campos bloqueados se houver recibo (RN-22) |
| PUT | `/caixa/{transaction}` | Atualiza | S-11 | |
| DELETE | `/caixa/{transaction}` | Exclusão lógica | S-10 | Bloqueada com recibo ativo |

### 5.6 Recibos

| Método | URL | Descrição | Tela | Acesso / Notas |
| :--- | :--- | :--- | :---: | :--- |
| GET | `/recibos` | Lista paginada | S-15 | 🔒 `status`, `cliente`, `de`, `ate`, `q` (número) |
| GET | `/recibos/novo?lancamento={id}` | Revisão antes de emitir | S-16 | 🔒 Exige perfil completo (`EnsureBusinessProfile`) |
| POST | `/recibos` | Emite recibo | S-16 | 🔒 `transaction_id`, `payer_name?`, `payer_document?`, `description?`; erro se já há recibo ativo |
| GET | `/recibos/{receipt}` | Detalhe (snapshot, link público `/r/{code}`) | S-17 | 🔒 |
| GET | `/recibos/{receipt}/pdf` | Gera e baixa/exibe o PDF sob demanda (`?disposition=inline\|attachment`) | — | 🔒 Renderizado em memória via DomPDF a partir do snapshot |
| POST | `/recibos/{receipt}/cancelar` | Cancela o recibo (`status = canceled`) | S-17 | 🔒 `reason?` |
| POST | `/recibos/{receipt}/email` | Envia recibo com PDF em anexo por e-mail (`defer()` / síncrono) | S-17 | 🔒 *throttle* 10/hora |
| GET | `/r/{code}` | Página pública de verificação do recibo | S-24 | 🌐 *throttle* 30/min |
| GET | `/r/{code}/pdf` | Download público do PDF pelo código UUID do recibo | S-24 | 🌐 *throttle* 30/min |

### 5.7 Dashboard, relatórios e páginas legais

| Método | URL | Descrição | Tela | Acesso / Parâmetros |
| :--- | :--- | :--- | :---: | :--- |
| GET | `/dashboard` | Cartões (receitas, despesas, resultado), série curta, últimos atendimentos, pendências de onboarding | S-06 | 🔒 `periodo=dia\|semana\|mes` |
| GET | `/relatorios/{aba?}` | Quatro abas (`faturamento`, `atendimentos`, `clientes`, `anual`) | S-18 | 🔒 `de`, `ate`, `agrupar=dia\|semana\|mes`, `status?`, `cliente?`, `ano` |
| GET | `/termos` · `/privacidade` | Termos de uso e Política de privacidade | S-23 | 🌐 |

## 6. Validações (regras principais)

Implementadas em **FormRequests** (única fonte de validação). O navegador apenas complementa com máscaras e atributos HTML.

| Recurso | Campo | Regra |
| :--- | :--- | :--- |
| Registro | `name` | obrigatório, 2–120 |
| | `email` | obrigatório, formato válido, único, ≤ 190, normalizado em minúsculas |
| | `password` | obrigatório, ≥ 8, confirmação; `uncompromised` (opcional) |
| | `accept_terms` | obrigatório `accepted` |
| Perfil do negócio | `display_name` | obrigatório para emitir, 2–150 |
| | `document_type/number` | `cpf`\|`cnpj` com dígitos verificadores válidos (**PV-15**) |
| | `phone` | telefone brasileiro válido → E.164 |
| | `state` | UF válida; `postal_code` 8 dígitos |
| | `logo` | imagem PNG/JPG/WebP, ≤ 1 MB, dimensões máx. 1000×1000; SVG proibido; reprocessada para remover metadados |
| Cliente | `name` | obrigatório, 2–150 |
| | `phone` | obrigatório, telefone brasileiro válido (RN-21) |
| | `email` | opcional, válido |
| | `document_number` | opcional, CPF/CNPJ válido |
| | `notes` | ≤ 2.000 |
| Atendimento | `client_id` | obrigatório, existente e do usuário |
| | `service_description` | obrigatório, 3–2.000 |
| | `service_at` | data/hora válida (limite futuro: **PV-02**) |
| | `status` | enum; transição conforme RN-04 |
| | `technical_notes` | ≤ 5.000 |
| | `cancel_reason` | ≤ 500 |
| Lançamento | `type` | `income`\|`expense` |
| | `amount_cents` | inteiro, 1 ≤ valor ≤ 999.999.999 (RN-02) |
| | `occurred_on` | data, ≤ hoje (RN-03) |
| | `description` | obrigatório, ≤ 255 |
| | `payment_method` | enum |
| | `category_id` | do usuário, não arquivada, mesmo `type` |
| | `appointment_id` | só `income`; do usuário; não cancelado; define `client_id` (RN-07) |
| Categoria | `name` | 2–60, único por tipo/usuário (comparação sem acento) |
| Recibo | `transaction_id` | receita do usuário, sem recibo ativo (RN-08) |
| | `payer_name` | obrigatório (herdado do cliente ou informado), 2–150 |
| | `payer_document` | opcional, CPF/CNPJ válido |
| | `description` | ≤ 500 (padrão: descrição do lançamento) |
| | `reason` (cancelar) | opcional, ≤ 500 |
| Envio | `recipient` | e-mail válido (canal `email`) |
| Relatórios | `de`/`ate` | datas válidas, `de ≤ ate`, intervalo ≤ 366 dias; `agrupar=dia` ≤ 92 dias |
| | `ano` | 2000 ≤ ano ≤ ano atual |

Valores inválidos de filtros na URL não geram erro: caem no padrão (ex.: período do mês corrente).

**Pacotes de apoio (avaliar manutenção no Sprint 0):** validação pt-BR de CPF/CNPJ (ex.: `laravellegends/pt-br-validator`) e telefone (ex.: `propaganistas/laravel-phone`); se algum estiver sem manutenção, implementar as regras em `app/Support` (CPF/CNPJ são poucas linhas).

## 7. Actions críticas (passo a passo macro)

| Action | Passos |
| :--- | :--- |
| Cadastro (`Registered`) | Cria usuário → registra `terms_accepted_at` → semeia categorias padrão (`DefaultCategoriesSeeder`) e cria `business_profile` vazio dentro da mesma transação → autentica |
| `ChangeAppointmentStatus` | Valida transição (RN-04) → atualiza `status` e carimbos (`completed_at`/`canceled_at`) → log |
| `RegisterTransaction` | Valida vínculo com atendimento (RN-07) → grava lançamento herdando cliente → (opcional) redireciona para emissão de recibo |
| `IssueReceipt` | Valida receita, ausência de recibo ativo e perfil completo → **transação de escrita imediata (`BEGIN IMMEDIATE`)**: calcula `COALESCE(MAX(number), 0) + 1` para `(user_id, year)`, cria recibo com snapshot imutável → *commit* → log |
| `CancelReceipt` | Valida status → marca `canceled` (`canceled_at`, `cancel_reason`) → log (o PDF gerado sob demanda passa a exibir a marca "CANCELADO" automaticamente) |
| `DeleteAccount` | Confirma senha → remove logo do usuário → em transação, exclui registros na **ordem topológica reversa** (`receipts` → `transactions` → `appointments` → `transaction_categories` → `clients` → `business_profile` → `user`) para respeitar as FKs `RESTRICT` do SQLite → invalida sessão → log |

**PDF do recibo:** template Blade com fonte incorporada (DejaVu Sans) para acentos e "R$"; recursos remotos desativados; todo conteúdo do usuário escapado; valor por extenso via `NumberFormatter` (ext-intl, `pt_BR`) com composição "reais e centavos" coberta por testes unitários. Renderizado sob demanda em memória (< 100 ms).

## 8. Tarefas em segundo plano e agendamentos

### 8.1 Execução de e-mails sem container de fila

Para manter a infraestrutura com **apenas 1 container (`app`)**, utiliza-se `QUEUE_CONNECTION=sync` (ou `defer()` do Laravel para enviar o e-mail logo após entregar a resposta HTTP ao navegador). Isso elimina o container `queue` e qualquer disputa de escrita em tabela `jobs`.

### 8.2 Agendamentos (`routes/console.php`)

| Tarefa | Frequência |
| :--- | :--- |
| **`backup:run`** (`VACUUM INTO` → compactar `.gz` → remover backups > 14 dias) | Diária (e antes de cada deploy) |
| `auth:clear-resets` | Diária |

## 9. Integrações

| Integração | Uso | Abordagem | Custo/Risco |
| :--- | :--- | :--- | :--- |
| **SMTP** | Recuperação de senha e recibo por e-mail | Provedor com plano gratuito (**PV-12**); Mailpit em dev | Limites do plano |
| **WhatsApp** | Envio de recibo | Sem API: Web Share API (arquivo PDF no mobile) → fallback `https://wa.me/{telefone}?text=…{/r/{code}}` | Sem confirmação de entrega (**PV-05**) |
| **PDF (DomPDF)** | Geração do recibo sob demanda | Biblioteca PHP local (`barryvdh/laravel-dompdf`) | Sem recursos remotos |
| **Telefone/CPF/CNPJ** | Validação | Classes utilitárias em `app/Support` (`CpfCnpj`, `PhoneNumber`) — zero dependência externa frágil | — |

## 10. Requisitos não funcionais do backend

| Tema | Diretriz |
| :--- | :--- |
| Desempenho | `Model::shouldBeStrict()` em dev/CI; *eager loading*; índices de `03`; paginação nas listagens; relatórios por SQL agregado; `php artisan optimize` e OPcache em produção |
| Concorrência | Modo WAL, `busy_timeout=5000`, transações de escrita imediata (`BEGIN IMMEDIATE`); sessão e cache em `file`; **teste de concorrência de emissão** (ver `08`) |
| Segurança | *Mass assignment* controlado (`$fillable`); `APP_DEBUG=false` em produção; cookies seguros; uploads validados; arquivo SQLite fora da pasta pública |
| Observabilidade | Logs em `stderr`; nunca registrar senha, token ou dados sensíveis |
| Testes | Pest com `assertInertia`: por rota, sucesso, validação, autorização e isolamento — detalhes em `08` |

## 11. Configurações relevantes (resumo)

| Arquivo | Ajustes |
| :--- | :--- |
| `.env` | `DB_CONNECTION=sqlite`; `DB_DATABASE=/data/db/database.sqlite`; `SESSION_DRIVER=file`; `QUEUE_CONNECTION=sync`; `CACHE_STORE=file`; `APP_TIMEZONE=America/Recife`; `APP_LOCALE=pt_BR` |
| `config/database.php` (conexão `sqlite`) | `foreign_key_constraints=true`; `journal_mode=WAL`; `busy_timeout=5000`; `synchronous=NORMAL`; `transaction_mode=IMMEDIATE` + UDF `unaccent()` registrada no `AppServiceProvider` |
| `bootstrap/app.php` | `trustProxies`, middleware `HandleInertiaRequests`, tratamento de páginas de erro Inertia e redirecionamento em `419` |
| `config/dompdf.php` | `enable_remote=false` (segurança contra SSRF) |

## 12. Pontos a validar

| PV | Assunto |
| :--- | :--- |
| PV-01 | Necessidade e escopo do perfil Administrador |
| PV-05 | Aceitação do compartilhamento sem API oficial do WhatsApp |
| PV-12 | Provedor SMTP e domínio de envio |
| PV-14 | Retenção de recibos vs. anonimização/exclusão |
| PV-16 | Verificação de e-mail obrigatória e abertura do cadastro |
| PV-20 | Prazo de expiração do link assinado |
| PV-25 | Destino externo do backup e criptografia |
| PV-29 | Duplicidade de telefone de clientes: proposta é avisar e permitir |
| PV-30 | Busca sem acento: coluna normalizada só em clientes (proposta) ou também em atendimentos |
