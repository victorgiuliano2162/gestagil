# 15 — Migração Mobile: Contrato de Backup (web <-> app)

> **Versão:** 1.0 · **Data:** 08/11/2026 · **Público:** quem implementará o app em React Native (repositório separado)
> **Fonte:** `app/Actions/Backup/ExportUserDataAction.php`, `RestoreUserDataAction.php`, `RestoreBackupRequest.php`, `BackupController.php`, `tests/Feature/UserBackupTest.php`
> **Relacionado:** `11-backup-restore-estrategia.md`, `13-migracao-modelo-dados.md`, `14-migracao-regras-negocio.md`

O arquivo de backup `.json` é a **ponte oficial** entre a versão web e o app, e também o único mecanismo de continuidade do app (os dados só existem no celular). Este documento descreve o formato **como está implementado hoje**, lista os **defeitos conhecidos** e define o **contrato alvo (v1 corrigido)** que web e app devem seguir.

> [!IMPORTANT]
> O app deve produzir e consumir o contrato alvo da seção 4. Os defeitos da seção 3 devem ser corrigidos na web **antes** de o app confiar em backups gerados por ela.

---

## 1. Envelope do arquivo

| Chave | Tipo | Descrição |
| :--- | :--- | :--- |
| `gestagil_backup_version` | inteiro | Versão do contrato. Atualmente `1` |
| `app_version` | texto | Versão do app que gerou (hoje fixo `"1.0.0"` na web) |
| `exported_at` | texto ISO 8601 | Data/hora da exportação, com deslocamento de fuso |
| `user` | objeto | `name` e `email` do titular (informativo; o app usa o nome para pré-preencher o onboarding e ignora o e-mail) |
| `metadata` | objeto | `total_clients`, `total_appointments`, `total_transactions`, `total_receipts` |
| `data` | objeto | Blocos de dados (seção 2) |

Nome sugerido do arquivo: `gestagil_backup_{slug-do-nome}_{AAAA-MM-DD_HHMMSS}.json` (formato da web).

---

## 2. Blocos de `data` e identificação de registros

| Bloco | Chave de identidade (merge) | Relacionamentos por |
| :--- | :--- | :--- |
| `business_profile` | Único (1 por titular) | n/a |
| `categories` | `(type, name_key)` | n/a |
| `clients` | `uuid`; na web também casa por `document_number` | n/a |
| `appointments` | `uuid` | `client_uuid` |
| `transactions` | `uuid` | `client_uuid`, `appointment_uuid`, `(category_type, category_name_key)` |
| `receipts` | `code` | `transaction_uuid`, `client_uuid` |

Os IDs numéricos **nunca** vão no arquivo. Todas as ligações usam UUID (e `code` nos recibos), o que torna o merge independente dos IDs de cada banco.

### 2.1 Campos por bloco (implementação atual)

| Bloco | Campos exportados |
| :--- | :--- |
| `categories[]` | `type`, `name`, `name_key`, `color` |
| `clients[]` | `uuid`, `name`, `search_name`, `phone_e164`, `document_type`, `document_number`, `email`, `notes`, `created_at`, `updated_at` |
| `appointments[]` | `uuid`, `client_uuid`, `service_description`, `service_at`, `status`, `technical_notes`, `expected_amount_cents`, `completed_at`, `canceled_at`, `cancel_reason`, `created_at`, `updated_at` |
| `transactions[]` | `uuid`, `type`, `amount_cents`, `occurred_on` (`YYYY-MM-DD`), `description`, `category_type`, `category_name_key`, `payment_method`, `appointment_uuid`, `client_uuid`, `notes`, `created_at`, `updated_at` |
| `receipts[]` | `code`, `transaction_uuid`, `client_uuid`, `year`, `number`, `status`, `issued_at`, `amount_cents`, `payment_method`, `description`, `payer_name`, `payer_document`, `issuer_snapshot` (objeto), `canceled_at`, `cancel_reason`, `created_at`, `updated_at` |
| `business_profile` | **Ver defeito 3.1** |

Datas e horas: ISO 8601 com deslocamento (`2026-11-08T14:30:00-03:00`).

---

## 3. Defeitos conhecidos da implementação atual (a corrigir na web)

| # | Gravidade | Defeito | Efeito | Correção alvo |
| :--- | :--- | :--- | :--- | :--- |
| 3.1 | **Alta** | O bloco `business_profile` exporta/importa campos que **não existem** na tabela (`trade_name`, `address_line`, `address_number`, `district`, `activity_type`, `receipt_footer`, `email`) e **omite** os reais (`street`, `number`, `complement`, `neighborhood`, `activity_description`, `logo_path`). `store_name`, `display_name`, `document_type`, `document_number`, `phone`, `postal_code`, `city`, `state` coincidem | Endereço, atividade e complemento do perfil são **perdidos silenciosamente** no backup/restore. Os testes atuais passam porque exportam `business_profile = null` | Usar exatamente as colunas reais (seção 4.1) e cobrir com teste que exporta um perfil completo |
| 3.2 | **Alta** | Exportação usa `withoutGlobalScopes()`, que remove também o filtro de exclusão lógica, e **não** exporta `deleted_at` | Clientes, atendimentos e lançamentos excluídos são exportados e **reaparecem como ativos** ao restaurar | Exportar `deleted_at` (nulo para ativos) e restaurá-lo; ou excluir registros removidos do export |
| 3.3 | Média | No modo merge, registro existente é **sempre sobrescrito**, sem comparar `updated_at` | Dado antigo do arquivo pode sobrescrever dado mais novo do aparelho | Regra "o mais recente vence" (seção 5.2) |
| 3.4 | Média | Restore de recibo localiza por `code` com `withoutGlobalScopes()` (global) e grava `user_id` do usuário atual | Um arquivo adulterado com o `code` de outro usuário poderia **reatribuir** o recibo dele. Irrelevante no app de titular único, **relevante na web** | Restringir a busca ao `user_id` do usuário atual |
| 3.5 | Média | Atendimento cujo cliente não é encontrado é ligado ao **primeiro cliente existente** | Atendimento associado ao cliente errado em silêncio | Rejeitar/pular o registro e reportá-lo no resumo |
| 3.6 | Média | A logo (`logo_path`) não faz parte do backup | Backup incompleto: a logo se perde ao trocar de aparelho | Incluir a logo (seção 4.2) |
| 3.7 | Baixa | Restore não valida o conteúdo de cada registro (tipos, tamanhos, enums) | Dado inválido gravado ou erro genérico no meio da transação | Validar antes de gravar (seção 6) |
| 3.8 | Baixa | `app_version` fixo | Sem rastreabilidade | Enviar a versão real |
| 3.9 | Baixa | Os documentos (`README.md`, `11-backup-restore-estrategia.md`) falam em "UUID v7", mas o código gera **UUID v4** (`Str::uuid()`) | Documentação imprecisa | Corrigir a documentação; o backup aceita **qualquer UUID válido** |

---

## 4. Contrato alvo (versão 1 corrigida)

### 4.1 `business_profile`

```json
{
  "display_name": "string",
  "store_name": "string | null",
  "activity_description": "string | null",
  "document_type": "cpf | cnpj | null",
  "document_number": "string | null",
  "phone": "string | null",
  "postal_code": "string | null",
  "street": "string | null",
  "number": "string | null",
  "complement": "string | null",
  "neighborhood": "string | null",
  "city": "string | null",
  "state": "string(2) | null",
  "logo": { "mime": "image/png | image/jpeg | image/webp", "data_base64": "string" } 
}
```

`logo` é opcional e `null` quando não há logo.

### 4.2 Logo no backup

- Gravar o arquivo da logo codificado em base64 dentro de `business_profile.logo` (limite prático: 2 MB originais, ~2,7 MB codificados).
- Na restauração, decodificar, gravar no armazenamento privado e atualizar `logo_path`. Importar uma logo substitui a atual (apagando a anterior).
- O `issuer_snapshot.logo_path` dos recibos guarda um caminho local e **não é portátil**; na importação, manter o valor como está e, no app, resolver a logo do PDF a partir da logo atual do perfil quando o caminho do snapshot não existir no aparelho.

### 4.3 Exclusão lógica

Cada registro de `clients`, `appointments` e `transactions` carrega `deleted_at` (`null` ou ISO 8601). Na restauração, o valor é gravado como veio.

### 4.4 Versionamento

| Regra | Detalhe |
| :--- | :--- |
| `gestagil_backup_version` | Inteiro. Mudanças compatíveis (campos novos opcionais) não mudam a versão. Mudanças incompatíveis incrementam |
| Leitor | Rejeita versão **maior** que a que conhece, com mensagem "Este backup foi gerado por uma versão mais nova do GestÁgil. Atualize o aplicativo." Aceita versões menores aplicando padrões aos campos ausentes |
| Campos desconhecidos | Ignorados sem erro |

---

## 5. Algoritmos de restauração

Toda restauração roda em **uma única transação**: ou aplica tudo, ou nada.

### 5.1 Ordem de gravação

1. `business_profile`
2. `categories`
3. `clients`
4. `appointments`
5. `transactions`
6. `receipts`

(Ordem exigida pelas chaves estrangeiras.)

### 5.2 Modo mesclar (`merge`)

| Bloco | Se já existe | Se não existe |
| :--- | :--- | :--- |
| business_profile | Campos do arquivo atualizam o perfil, **somente se o perfil local ainda estiver vazio ou o arquivo for mais recente** (decisão de produto a confirmar); na dúvida, manter o local | Criar |
| categories | Reutilizar a categoria local (por `type` + `name_key`); não alterar | Criar |
| clients | Casar por `uuid`. Atualizar **somente se** `updated_at` do arquivo > `updated_at` local | Criar com o `uuid` do arquivo |
| appointments | Casar por `uuid`; mesma regra de `updated_at` | Criar (cliente resolvido por `client_uuid`) |
| transactions | Casar por `uuid`; mesma regra de `updated_at` | Criar (relações resolvidas por UUID/chave de categoria) |
| receipts | Casar por `code`. Recibo é **imutável** exceto cancelamento: se o do arquivo estiver `canceled` e o local `issued`, aplicar o cancelamento; caso contrário, manter o local | Criar |

Regras adicionais:

- **Cliente por documento (web):** a implementação atual também casa clientes por `document_number` quando o `uuid` não coincide. Isso une cadastros do mesmo CPF/CNPJ de origens diferentes. **Decisão para o app:** manter esse comportamento, mas, ao unir por documento, **adotar o `uuid` já existente localmente** e mapear o `uuid` do arquivo para ele (para que atendimentos e lançamentos do arquivo apontem para o cliente correto).
- **Colisão de numeração de recibo:** se o par `(year, number)` do arquivo já existir localmente para **outro** `code`, atribuir `number = MAX(number do ano) + 1` (comportamento atual da web). Isso altera o número impresso do recibo importado; o resumo da restauração deve listar esses casos.
- **Relação ausente:** se `client_uuid`, `appointment_uuid` ou `transaction_uuid` não for resolvido: lançamento e atendimento **sem cliente** são rejeitados (atendimento exige cliente); lançamento mantém `client_id`/`appointment_id` nulos; recibo sem lançamento resolvido é **pulado** e contado como ignorado.
- **Contagens do resumo:** registros criados, atualizados, ignorados (e motivo).

### 5.3 Modo sobrescrever (`replace`)

Precondições obrigatórias (**double check**):

1. O usuário escolhe explicitamente "Sobrescrever tudo".
2. A interface exige a confirmação digitada (palavra `SOBRESCREVER`), em uma tela de bloqueio separada.
3. A camada de serviço **rejeita** a operação se a confirmação explícita não vier (na web: `confirmed_overwrite = true`, erro `422` caso contrário). O app deve aplicar a mesma barreira no serviço, não só na tela.

Passos:

1. **Snapshot de segurança:** exportar o estado atual para um arquivo de contingência (web: `storage/app/backups/pre_restore_user_{id}_{AAAAMMDD_HHMMSS}.json`; app: diretório privado, ex.: `pre_restore_{AAAAMMDD_HHMMSS}.json`) **antes** de qualquer alteração.
2. Em transação: apagar fisicamente `receipts`, `transactions`, `appointments`, `clients` (nesta ordem). Categorias e perfil da loja **não** são apagados; são mesclados (perfil atualizado, categorias reutilizadas/criadas).
3. Gravar os blocos na ordem da seção 5.1.
4. Em falha, reverter tudo e manter o snapshot.

Retenção do snapshot: manter os N mais recentes (sugestão N = 3) e oferecer "Desfazer última restauração" que restaura o mais recente em modo `replace`.

---

## 6. Validação do arquivo (antes de gravar)

| Verificação | Falha |
| :--- | :--- |
| É JSON válido e objeto | "O arquivo fornecido não é um backup válido do GestÁgil." |
| Possui `gestagil_backup_version` e `data` | Idem |
| Versão suportada (seção 4.4) | Mensagem de versão mais nova |
| Tamanho máximo | 20 MB (limite da web); no app, o mesmo |
| Para cada registro: tipos corretos, enums válidos (`status`, `type`, `payment_method`, `document_type`), `amount_cents` inteiro > 0 em lançamentos e recibos, UUID bem formado, datas parseáveis, tamanhos máximos das colunas (`13`) | Registro **rejeitado e listado** no relatório; os demais seguem. Se a proporção de rejeitados for alta, abortar e avisar |

Pré-visualização obrigatória antes de confirmar (modo mesclar e sobrescrever): quantos registros serão criados, atualizados, ignorados e (no replace) quantos serão apagados.

---

## 7. Particularidades do app

| Tema | Regra |
| :--- | :--- |
| Titular | Ignorar `user.email`. Se o app ainda não tem titular (primeiro uso), oferecer "Restaurar de um backup" já no onboarding, pré-preenchendo o nome |
| Origem do arquivo | Seletor de documentos do sistema; compartilhamento para exportar |
| Google Drive | Mesmo arquivo `.json`, gravado na pasta oculta do app (`appDataFolder`); fora do escopo deste contrato |
| Importar backup da web | Caminho feliz da migração de um negócio existente: exportar na web, importar no app em modo `replace` (aparelho novo) ou `merge` |
| Exportar do app para a web | A web aceita o mesmo arquivo, desde que a web corrija 3.1 e 3.2 |
| UUID | Gerar UUID v4 ou v7 no app. A web aceita qualquer UUID |

---

## 8. Casos de teste do contrato (portar para ambos os lados)

| # | Cenário | Resultado esperado |
| :--- | :--- | :--- |
| 1 | Exportar base vazia | Arquivo válido, blocos vazios, `metadata` com zeros |
| 2 | Exportar perfil completo (com endereço e atividade) e restaurar | Todos os campos do perfil preservados (cobre 3.1) |
| 3 | Exportar com cliente excluído e restaurar | Cliente continua excluído (cobre 3.2) |
| 4 | Round-trip completo: exportar, `replace` em banco vazio, exportar de novo | Os dois arquivos são equivalentes (ignorando `exported_at`) |
| 5 | Merge de cliente existente com `updated_at` do arquivo **mais antigo** | Dado local preservado (cobre 3.3) |
| 6 | Merge de cliente existente com `updated_at` do arquivo **mais novo** | Dado atualizado |
| 7 | Merge com cliente de mesmo `document_number` e `uuid` diferente | Um único cliente; atendimentos do arquivo apontam para ele |
| 8 | Merge com colisão de `(year, number)` de recibo com `code` diferente | Recibo importado recebe novo número; reportado no resumo |
| 9 | Atendimento no arquivo com `client_uuid` inexistente | Rejeitado e reportado (cobre 3.5) |
| 10 | `replace` sem confirmação explícita | Operação rejeitada e nenhum dado alterado |
| 11 | `replace` com falha no meio | Banco volta ao estado anterior; snapshot permanece |
| 12 | Arquivo com versão maior que a suportada | Rejeitado com mensagem de atualização |
| 13 | Arquivo com campo desconhecido | Ignorado sem erro |
| 14 | Arquivo gerado pela web (real) importado no app | Contagens batem com `metadata` |
| 15 | Arquivo gerado pelo app importado na web | Contagens batem com `metadata` |
