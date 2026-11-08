# 11 — Estratégia de Backup e Restauração de Dados (Local & Google Drive)

> **Versão:** 1.0 · **Data:** 08/11/2026 · **Status:** Aprovado para execução
> **Depende de:** `01-arquitetura.md`, `02-requisitos.md`, `03-modelo-dados.md`, `04-backend-rotas.md`, `05-frontend-telas.md`

---

## 1. Contexto e Motivação

O **GestAgil** é uma plataforma concebida para **execução local (self-hosted)** no computador ou servidor de cada microempreendedor/prestador de serviços, com distribuição gratuita. Nessa arquitetura:

1. **Responsabilidade do Usuário:** Como não há infraestrutura de nuvem central cobrada do usuário, a posse e a custódia das cópias de segurança (backups) pertencem ao próprio usuário.
2. **Facilidade Operacional (Zero Fricção):** O público-alvo (mecânicos, encanadores, eletricistas, autônomos) não possui conhecimentos avançados de TI. Qualquer processo que exija criar contas em consoles de nuvem (Google Cloud Console, Firebase Console, Service Accounts, chaves de API) inviabiliza o uso da funcionalidade.
3. **Multi-inquilino compartilhado no SQLite:** O SQLite do GestAgil possui isolamento por `user_id`. Por esse motivo, backups do arquivo físico `.sqlite` são **inviáveis** para usuários individuais, pois vazariam dados de outros usuários e causariam colisões destrutivas na restauração.

---

## 2. Decisões Arquiteturais (ADRs)

### ADR-Backup-01: Exportação Estruturada em JSON em vez de arquivo `.sqlite`
- **Decisão:** O backup é exportado como um arquivo de texto estruturado (`.json`), versionado e filtrado exclusivamente pelos registros do usuário logado (`WHERE user_id = :auth_id`).
- **Motivo:** Evita vazamento de dados de outros usuários (segurança multi-tenant), desacopla o backup da versão física do banco de dados e permite mesclagens inteligentes.

### ADR-Backup-02: Adoção de UUIDs de Sincronização
- **Decisão:** Adicionar coluna `uuid` indexada nas entidades centrais (`clients`, `appointments`, `transactions`), integrando com o `code` (UUID) já existente em `receipts`.
- **Motivo:** IDs sequenciais inteiros colidem inevitavelmente ao mesclar dados de diferentes computadores ou bases restauradas. Com UUIDs (v7 ordenado por tempo), cada registro gerado em qualquer máquina é globalmente único e identificável sem risco de sobreposição de chaves primárias.

### ADR-Backup-03: Suporte aos Dois Modos de Restauração (Merge vs Replace)
- **Modo Mesclar (Merge - Padrão/Recomendado):** Compara os UUIDs do arquivo com a base local. Registros já existentes são atualizados se o backup for mais recente; registros inexistentes são inseridos sem tocar nos dados pré-existentes.
- **Modo Sobrescrever (Replace):** Substitui todo o conjunto de dados do usuário pelos registros do arquivo de backup.

### ADR-Backup-04: Exigência Obrigatória de Double Check na Sobrescrita
- **Decisão:** Quando o usuário seleciona o modo "Sobrescrever", a interface e o backend impõem um **Double Check** obrigatório:
  1. No frontend, um modal de aviso em destaque exige confirmação explícita (digitar a palavra `SOBRESCREVER` ou confirmação dupla de risco).
  2. No backend, a requisição é rejeitada com erro `422 Unprocessable Content` se o parâmetro de confirmação explícita (`confirmed_overwrite`) não for verdadeiro.
  3. Antes de apagar os dados do usuário, o backend gera um snapshot de contingência em `storage/app/backups/pre_restore_{user_id}_{timestamp}.json`.

### ADR-Backup-05: Fluxo de Armazenamento Híbrido (Local 1-Clique + Google Drive)
- **Base Local (.json):** Download e upload imediatos pelo navegador, operando 100% offline e sem custos.
- **Google Drive (Nuvem Pessoal):** Utilização do Google Identity Services (GIS) no frontend, conectando à pasta oculta de dados de aplicativo (`appDataFolder`) da conta Google do próprio usuário, com uma credencial pública OAuth da comunidade GestAgil embutida, oferecendo a experiência de 1 clique igual ao Draw.io e WhatsApp.

---

## 3. Como as Decisões Reverberam no Código

### 3.1 Banco de Dados e Migrations
- **Nova Migration:** `add_uuid_to_clients_appointments_transactions_tables.php`.
  - Adiciona `uuid char(36) NOT NULL UNIQUE` nas tabelas `clients`, `appointments` e `transactions`.
  - Para registros já existentes, preenche com UUIDs gerados aleatoriamente antes de aplicar a restrição de unicidade.

### 3.2 Eloquent Models
- Models `Client`, `Appointment` e `Transaction` passam a utilizar o hook `booted()` (ou trait `HasUuid`) para gerar automaticamente `Str::uuid()` no evento `creating` caso o `uuid` não tenha sido fornecido.

### 3.3 Camada de Negócio (Actions)
- **`App\Actions\Backup\ExportUserDataAction`:**
  - Extrai os dados do usuário (`business_profile`, `categories`, `clients`, `appointments`, `transactions`, `receipts`).
  - Serializa em um JSON envelopado com chave de versão (`gestagil_backup_version: 1`), metadados e data de extração.
- **`App\Actions\Backup\RestoreUserDataAction`:**
  - Valida o schema do JSON e versão compatível.
  - Executa a importação dentro de uma transação atômica (`DB::transaction`).
  - **Se Merge:** realiza `upsert` baseado no `uuid` mantendo integridade relacional.
  - **Se Replace:** valida o double check, cria snapshot de segurança e substitui os dados em ordem topológica reversa (recibos -> transações -> atendimentos -> clientes).

### 3.4 Rotas e Controller
- **Controller:** `App\Http\Controllers\BackupController`
  - `GET /backup` (`backup.index`): renderiza a página de gestão de backups.
  - `GET /backup/export` (`backup.export`): dispara o download do arquivo JSON.
  - `POST /backup/restore` (`backup.restore`): processa o arquivo enviado com validação FormRequest (`mode` e `confirmed_overwrite`).
- **Rotas:** Registradas em `routes/web.php` sob o grupo de autenticação `auth`.

### 3.5 Interface do Usuário (Vue 3 + Inertia)
- **Página:** `resources/js/Pages/Backup/Index.vue`
  - Card 1: **Exportar Dados** com botão para download imediato do arquivo `.json`.
  - Card 2: **Restaurar Dados** com drag & drop / seletor de arquivo `.json`.
  - Opções em radio buttons: "Mesclar dados (Recomendado)" vs "Sobrescrever tudo".
  - Seletor de Sobrescrita aciona um **Modal de Double Check** bloqueante com aviso e campo de texto de confirmação obrigatório antes de habilitar o botão final.
  - Feedback visual claro via toasts/banners e relatório de registros afetados.
- **Navegação:** Inclusão de atalho "Backup" no menu de configurações / dropdown do usuário em `AuthenticatedLayout.vue`.

### 3.6 Testes Automatizados (Pest)
- Suíte `tests/Feature/UserBackupTest.php` validando:
  - Exportação e integridade do payload JSON.
  - Isolamento multi-tenant na exportação.
  - Restauração no modo Merge sem duplicações indevidas.
  - Restauração no modo Replace com verificação obrigatória de double check.
  - Rejeição de Replace sem a confirmação de double check.
  - Isolamento multi-tenant garantindo que a restauração não afeta dados de outros usuários.
