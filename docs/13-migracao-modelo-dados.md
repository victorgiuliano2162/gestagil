# 13 — Migração Mobile: Modelo de Dados Local

> **Versão:** 1.0 · **Data:** 08/11/2026 · **Público:** quem implementará o app em React Native (repositório separado)
> **Fonte:** `database/migrations/*`, `app/Models/*`, `app/Enums/*`, `database/seeders/DefaultCategoriesSeeder.php` (estado real do código, não apenas o planejado em `03-modelo-dados.md`)
> **Relacionado:** `12-plano-app-android.md`, `14-migracao-regras-negocio.md`, `15-migracao-contrato-backup.md`

---

## 1. Premissas do app

| Premissa | Consequência no esquema |
| :--- | :--- |
| **Um aparelho = um titular** (decisão tomada) | Não existem `users`, `sessions`, `password_reset_tokens`, `user_id`, `UserScope`. Todas as consultas operam sobre o banco inteiro |
| Dados 100% locais | Banco SQLite no armazenamento privado do app, criptografado (SQLCipher) |
| Sem verificação pública de recibo (decisão tomada) | O recibo mantém o `code` (UUID), mas não há página pública. Ver regra de PDF em `14` |
| Compatível com backup da web | Nomes de tabelas e colunas permanecem iguais aos da web, para o import/export JSON ser direto |

### 1.1 O que sai e o que entra

| Web | Mobile |
| :--- | :--- |
| `users` (name, email, password, `terms_accepted_at`, ...) | Tabela única `owner` (1 linha): `name`, `terms_accepted_at`, `created_at`, `updated_at`. Sem e-mail nem senha |
| `business_profiles.user_id` (1:1) | `business_profiles` com **uma única linha** (garantida por `CHECK (id = 1)`) |
| `user_id` em todas as tabelas | Coluna removida |
| `cache`, `jobs`, `sessions`, `password_reset_tokens` | Não existem |
| Nada | `schema_migrations` (controle de versão do banco no aparelho) e `app_settings` (chave/valor: PIN habilitado, último backup, etc.) |

---

## 2. Tabelas (estado real do repositório)

> Convenção de tipos: `text` guarda datas/horas e UUID; `integer` guarda inteiros e booleanos; dinheiro é sempre **centavos inteiros (`*_cents`)**.

### 2.1 `business_profiles` (perfil da loja)

Migrations: `2026_11_06_105000_create_business_profiles_table` e `2026_11_06_114500_add_store_name_and_activity_to_business_profiles_table`.

| Coluna | Tipo | Nulo | Observação |
| :--- | :--- | :--- | :--- |
| id | integer PK | não | Sempre 1 no app |
| display_name | text (max 160) | não | Padrão no primeiro uso: nome do titular |
| store_name | text (max 160) | sim | Nome fantasia da loja |
| activity_description | text | sim | Máx. 1000 caracteres (validação) |
| document_type | text (max 10) | sim | `cpf` ou `cnpj` |
| document_number | text (max 20) | sim | |
| phone | text (max 20) | sim | |
| postal_code | text (max 10) | sim | |
| street | text (max 160) | sim | |
| number | text (max 20) | sim | |
| complement | text (max 60) | sim | |
| neighborhood | text (max 80) | sim | |
| city | text (max 80) | sim | |
| state | text (2) | sim | UF, exatamente 2 letras |
| logo_path | text (max 255) | sim | Caminho **relativo** do arquivo no diretório privado do app |
| created_at, updated_at | text | sim | |

### 2.2 `transaction_categories`

| Coluna | Tipo | Nulo | Observação |
| :--- | :--- | :--- | :--- |
| id | integer PK | não | |
| type | text (max 10) | não | `income` ou `expense` |
| name | text (max 80) | não | |
| name_key | text (max 80) | não | Slug do nome (ver 4.1) |
| color | text (max 20) | sim | Hexadecimal (`#10B981`) |
| created_at, updated_at | text | sim | |

Restrições: `UNIQUE (type, name_key)`; índice em `(type)`.

### 2.3 `clients`

| Coluna | Tipo | Nulo | Observação |
| :--- | :--- | :--- | :--- |
| id | integer PK | não | |
| uuid | text (UUID) | não | Único. Adicionado pela migration `2026_11_08_143000` |
| name | text (max 160) | não | |
| search_name | text (max 160) | não | Derivado de `name`: minúsculas e sem acento (ver 4.2). Indexado |
| phone_e164 | text (max 20) | sim | Validação: `^\+?[0-9]{10,15}$` |
| document_type | text (max 10) | sim | `cpf` ou `cnpj` |
| document_number | text (max 20) | sim | |
| email | text (max 160) | sim | |
| notes | text | sim | Máx. 1000 (validação) |
| created_at, updated_at | text | sim | |
| deleted_at | text | sim | Soft delete |

Índice: `(search_name)`.

### 2.4 `appointments`

| Coluna | Tipo | Nulo | Observação |
| :--- | :--- | :--- | :--- |
| id | integer PK | não | |
| uuid | text (UUID) | não | Único |
| client_id | integer FK -> clients | não | `ON DELETE RESTRICT` |
| service_description | text | não | Máx. 2000 (validação) |
| service_at | text (datetime) | não | Data e hora local |
| status | text (max 15) | não | `in_progress` (padrão), `completed`, `canceled` |
| technical_notes | text | sim | Máx. 2000 |
| expected_amount_cents | integer | sim | Valor previsto |
| completed_at | text (datetime) | sim | Preenchido ao concluir |
| canceled_at | text (datetime) | sim | Preenchido ao cancelar |
| cancel_reason | text (max 500) | sim | |
| created_at, updated_at | text | sim | |
| deleted_at | text | sim | Soft delete |

Índices: `(status)`, `(service_at)`.

### 2.5 `transactions` (lançamentos de caixa)

| Coluna | Tipo | Nulo | Observação |
| :--- | :--- | :--- | :--- |
| id | integer PK | não | |
| uuid | text (UUID) | não | Único |
| type | text (max 10) | não | `income` ou `expense` |
| amount_cents | integer | não | Maior que zero (validação) |
| occurred_on | text (date `YYYY-MM-DD`) | não | |
| description | text (max 255) | não | |
| category_id | integer FK -> transaction_categories | sim | `ON DELETE SET NULL` |
| payment_method | text (max 10) | não | `pix` (padrão), `cash`, `card`, `other` |
| appointment_id | integer FK -> appointments | sim | `ON DELETE RESTRICT` |
| client_id | integer FK -> clients | sim | `ON DELETE SET NULL` |
| notes | text | sim | Máx. 1000 |
| created_at, updated_at | text | sim | |
| deleted_at | text | sim | Soft delete |

Índices: `(type, occurred_on)`, `(occurred_on)`.

### 2.6 `receipts`

| Coluna | Tipo | Nulo | Observação |
| :--- | :--- | :--- | :--- |
| id | integer PK | não | |
| transaction_id | integer FK -> transactions | não | `ON DELETE RESTRICT` |
| client_id | integer FK -> clients | sim | `ON DELETE SET NULL` |
| code | text (UUID) | não | Único. Identificador estável do recibo (usado no backup) |
| year | integer | não | Ano de emissão |
| number | integer | não | Sequencial dentro do ano |
| status | text (max 10) | não | `issued` (padrão) ou `canceled` |
| issued_at | text (datetime) | não | |
| amount_cents | integer | não | **Cópia** do valor do lançamento no momento da emissão |
| payment_method | text (max 15) | não | `pix` (padrão), `cash`, `card`, `other` |
| description | text | não | Máx. 500 (validação) |
| payer_name | text (max 150) | não | |
| payer_document | text (max 20) | sim | |
| issuer_snapshot | text (JSON) | não | Fotografia do emitente (ver 3) |
| canceled_at | text (datetime) | sim | |
| cancel_reason | text (max 500) | sim | |
| created_at, updated_at | text | sim | |

Restrições: `UNIQUE (code)`, `UNIQUE (year, number)`; índice `(issued_at)`. Recibos **não têm** soft delete.

---

## 3. Estrutura do `issuer_snapshot` (JSON)

Gravado na emissão e **nunca alterado depois**, mesmo que a loja mude de nome ou endereço.

```json
{
  "store_name": "string (store_name, senão display_name, senão nome do titular)",
  "display_name": "string (display_name, senão nome do titular)",
  "document_type": "cpf | cnpj (padrão 'cpf')",
  "document_number": "string (padrão '')",
  "phone": "string (padrão '')",
  "full_address": "string | null",
  "logo_path": "string | null"
}
```

`full_address` é montado assim (somente se `street` existir): `"{street}, {number}"`, acrescido de `" - {neighborhood}"` se houver bairro, e de `" ({city}/{state})"` se houver cidade **e** estado.

---

## 4. Valores derivados e sementes

### 4.1 `name_key` das categorias

`name_key = slug(name)` (equivalente a `Str::slug` do Laravel: minúsculas, sem acentos, qualquer sequência de caracteres não alfanuméricos vira um único hífen). Exemplos:

| name | name_key |
| :--- | :--- |
| Prestação de Serviços | `prestacao-de-servicos` |
| Fornecedores / Mercadorias | `fornecedores-mercadorias` |
| Energia / Água / Internet | `energia-agua-internet` |

### 4.2 `search_name` dos clientes

`search_name = lowercase(removerAcentos(name))`, recalculado a cada gravação do cliente (equivalente a `Str::lower(Str::ascii($name))`).

### 4.3 Categorias padrão (semear no primeiro uso)

| Tipo | Nome | Cor |
| :--- | :--- | :--- |
| income | Vendas de Produtos | `#10B981` |
| income | Prestação de Serviços | `#059669` |
| income | Outras Receitas | `#34D399` |
| expense | Fornecedores / Mercadorias | `#EF4444` |
| expense | Aluguel / Condomínio | `#DC2626` |
| expense | Energia / Água / Internet | `#F59E0B` |
| expense | Marketing e Vendas | `#8B5CF6` |
| expense | Impostos e Taxas | `#6B7280` |
| expense | Outras Despesas | `#9CA3AF` |

A semente é idempotente (`firstOrCreate` por `(type, name_key)`).

### 4.4 Enums

| Enum | Valores | Rótulo pt-BR | Cor |
| :--- | :--- | :--- | :--- |
| `AppointmentStatus` | `in_progress` | Em Andamento | amber |
| | `completed` | Concluído | emerald |
| | `canceled` | Cancelado | rose |
| `PaymentMethod` | `pix` | Pix | |
| | `cash` | Dinheiro | |
| | `card` | Cartão | |
| | `other` | Outro | |

---

## 5. DDL proposta para o SQLite do app (migration 0001)

```sql
PRAGMA foreign_keys = ON;

CREATE TABLE owner (
  id INTEGER PRIMARY KEY CHECK (id = 1),
  name TEXT NOT NULL,
  terms_accepted_at TEXT,
  created_at TEXT,
  updated_at TEXT
);

CREATE TABLE app_settings (
  key TEXT PRIMARY KEY,
  value TEXT
);

CREATE TABLE business_profiles (
  id INTEGER PRIMARY KEY CHECK (id = 1),
  display_name TEXT NOT NULL,
  store_name TEXT,
  activity_description TEXT,
  document_type TEXT CHECK (document_type IN ('cpf','cnpj')),
  document_number TEXT,
  phone TEXT,
  postal_code TEXT,
  street TEXT,
  number TEXT,
  complement TEXT,
  neighborhood TEXT,
  city TEXT,
  state TEXT,
  logo_path TEXT,
  created_at TEXT,
  updated_at TEXT
);

CREATE TABLE transaction_categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  type TEXT NOT NULL CHECK (type IN ('income','expense')),
  name TEXT NOT NULL,
  name_key TEXT NOT NULL,
  color TEXT,
  created_at TEXT,
  updated_at TEXT,
  UNIQUE (type, name_key)
);

CREATE TABLE clients (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  search_name TEXT NOT NULL,
  phone_e164 TEXT,
  document_type TEXT CHECK (document_type IN ('cpf','cnpj')),
  document_number TEXT,
  email TEXT,
  notes TEXT,
  created_at TEXT,
  updated_at TEXT,
  deleted_at TEXT
);
CREATE INDEX clients_search_name_index ON clients (search_name);

CREATE TABLE appointments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT NOT NULL UNIQUE,
  client_id INTEGER NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
  service_description TEXT NOT NULL,
  service_at TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'in_progress'
    CHECK (status IN ('in_progress','completed','canceled')),
  technical_notes TEXT,
  expected_amount_cents INTEGER CHECK (expected_amount_cents IS NULL OR expected_amount_cents >= 0),
  completed_at TEXT,
  canceled_at TEXT,
  cancel_reason TEXT,
  created_at TEXT,
  updated_at TEXT,
  deleted_at TEXT
);
CREATE INDEX appointments_status_index ON appointments (status);
CREATE INDEX appointments_service_at_index ON appointments (service_at);

CREATE TABLE transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT NOT NULL UNIQUE,
  type TEXT NOT NULL CHECK (type IN ('income','expense')),
  amount_cents INTEGER NOT NULL CHECK (amount_cents > 0),
  occurred_on TEXT NOT NULL,
  description TEXT NOT NULL,
  category_id INTEGER REFERENCES transaction_categories(id) ON DELETE SET NULL,
  payment_method TEXT NOT NULL DEFAULT 'pix'
    CHECK (payment_method IN ('pix','cash','card','other')),
  appointment_id INTEGER REFERENCES appointments(id) ON DELETE RESTRICT,
  client_id INTEGER REFERENCES clients(id) ON DELETE SET NULL,
  notes TEXT,
  created_at TEXT,
  updated_at TEXT,
  deleted_at TEXT
);
CREATE INDEX transactions_type_occurred_on_index ON transactions (type, occurred_on);
CREATE INDEX transactions_occurred_on_index ON transactions (occurred_on);

CREATE TABLE receipts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  transaction_id INTEGER NOT NULL REFERENCES transactions(id) ON DELETE RESTRICT,
  client_id INTEGER REFERENCES clients(id) ON DELETE SET NULL,
  code TEXT NOT NULL UNIQUE,
  year INTEGER NOT NULL,
  number INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'issued' CHECK (status IN ('issued','canceled')),
  issued_at TEXT NOT NULL,
  amount_cents INTEGER NOT NULL CHECK (amount_cents > 0),
  payment_method TEXT NOT NULL DEFAULT 'pix',
  description TEXT NOT NULL,
  payer_name TEXT NOT NULL,
  payer_document TEXT,
  issuer_snapshot TEXT NOT NULL,
  canceled_at TEXT,
  cancel_reason TEXT,
  created_at TEXT,
  updated_at TEXT,
  UNIQUE (year, number)
);
CREATE INDEX receipts_issued_at_index ON receipts (issued_at);

CREATE TABLE schema_migrations (
  version TEXT PRIMARY KEY,
  applied_at TEXT NOT NULL
);
```

> [!NOTE]
> Os `CHECK` acima **não existem** nas migrations da web (a web valida apenas na aplicação). Eles são recomendados no app como defesa em profundidade. Ao importar um backup da web, validar cada registro antes de gravar para que um `CHECK` não derrube a importação inteira (ver `15`).

---

## 6. Configuração do banco no aparelho

| Item | Valor | Origem |
| :--- | :--- | :--- |
| `PRAGMA foreign_keys` | `ON` em toda conexão | `DB_FOREIGN_KEYS=true` na web |
| `journal_mode` | `WAL` | `config/database.php` |
| `synchronous` | `NORMAL` | `config/database.php` |
| `busy_timeout` | 5000 ms (irrelevante com um único escritor, mas inofensivo) | `config/database.php` |
| Escritas compostas | Dentro de transação (`BEGIN IMMEDIATE`) | `transaction_mode=IMMEDIATE` |
| Criptografia | SQLCipher; chave aleatória guardada no armazenamento seguro do sistema | Decisão do app |

---

## 7. Regras de leitura obrigatórias (soft delete)

| Regra | Detalhe |
| :--- | :--- |
| Listas, buscas, totais e relatórios de `clients`, `appointments`, `transactions` | Sempre filtrar `deleted_at IS NULL` |
| Cliente exibido dentro de atendimento, lançamento ou recibo | Carregar **mesmo excluído** (a web usa `withTrashed()` nessas relações) para não perder o nome no histórico |
| Excluir cliente/atendimento/lançamento | Preencher `deleted_at` (nunca `DELETE`) |
| Recibos | Nunca excluídos; apenas `status = 'canceled'` |
| "Apagar todos os dados" (LGPD) | Único caso de exclusão física; ordem: `receipts`, `transactions`, `appointments`, `transaction_categories`, `clients`, `business_profiles`, arquivo da logo, `owner` |

---

## 8. Datas e fuso horário

- A web grava datas e horas **no horário local do servidor** (`America/Recife`), como texto sem fuso (`YYYY-MM-DD HH:MM:SS`); `occurred_on` é `YYYY-MM-DD`.
- **Proposta para o app:** usar o fuso do aparelho e gravar no mesmo formato de texto local. O Brasil não adota horário de verão desde 2019, portanto o comportamento é equivalente em todo o território.
- Em backups, as datas são exportadas em ISO 8601 com deslocamento (ex.: `2026-11-08T14:30:00-03:00`). Na importação, converter para o horário local do aparelho antes de gravar.

---

## 9. Estratégia de migrations no aparelho

1. Cada migration é um arquivo SQL numerado e imutável (`0001_initial.sql`, `0002_...`).
2. Na abertura do app: ler `schema_migrations`, aplicar as pendentes **em ordem, cada uma em transação**.
3. **Antes de aplicar qualquer migration pendente, copiar o arquivo do banco** para um arquivo de segurança (`pre_migration_{versao}.db`) e só descartá-lo após sucesso.
4. Em falha: reverter a transação, restaurar a cópia e exibir erro claro (sem perder dados).
5. Nunca editar uma migration já publicada; criar uma nova.
6. Testes obrigatórios de atualização: criar banco na versão N-1, popular, migrar para N e validar integridade (`PRAGMA integrity_check` e `PRAGMA foreign_key_check`).

---

## 10. Divergências entre a documentação antiga e o código real

| Item em `03-modelo-dados.md` | Código real | Decisão para o app |
| :--- | :--- | :--- |
| Tabela `transaction_categories` com `is_default` e `archived_at` | Colunas **não existem** | Seguir o código real (sem essas colunas) |
| `business_profiles.trade_name`, `receipt_footer`, `email`, `address_line`, ... | Colunas reais: `store_name`, `activity_description`, `street`, `number`, `complement`, `neighborhood` | Seguir o código real |
| `CHECK (amount_cents > 0)` e índices únicos parciais | Não existem nas migrations | Adotar `CHECK` no app (seção 5) |
| `receipts` com `id` e `code`; `unique(user_id, year, number)` | `UNIQUE (year, number)` (sem `user_id`, pois é único titular) | Conforme seção 5 |
