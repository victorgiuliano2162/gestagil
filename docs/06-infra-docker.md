# 06 — Infraestrutura Docker

> **Versão:** 0.3 (revisão enxuta: container único `app` em produção + SQLite) · **Data:** 06/11/2026 · Depende de: `01-arquitetura.md`, `03-modelo-dados.md`, `04-backend-rotas.md`
> **Redução radical para simplicidade operacional:** **1 único serviço em produção** (`app`, com FrankenPHP + Laravel + assets compilados) e **2 extras opcionais em desenvolvimento** (`vite`, `mailpit`). Saem containers separados de `queue` e `scheduler`, PostgreSQL, Redis, Nginx e Adminer.

## 1. Visão geral

| Ambiente | O que roda |
| :--- | :--- |
| **Produção** | `app` (FrankenPHP: servidor web + PHP + Laravel + assets do Vite compilados) |
| **Desenvolvimento** | `app` (com código montado por *bind mount*) + `vite` (HMR) + `mailpit` (captura e-mails) |

### Estrutura de arquivos

```
gestagil/                     (projeto Laravel na raiz)
├── compose.yaml              base (serviço app e volumes)
├── compose.override.yaml     desenvolvimento (bind mount, vite, mailpit) — carregado automaticamente
├── compose.prod.yaml         produção (imagem pronta, limites, TLS)
├── docker/
│   ├── Dockerfile            multi-stage: assets (Node) → dependências (Composer) → runtime
│   ├── Caddyfile             configuração do servidor (FrankenPHP/Caddy): porta, cabeçalhos, TLS opcional
│   ├── php/                  ajustes do php.ini (OPcache, upload, fuso)
│   └── entrypoint.sh         inicializa banco SQLite se não existir, `optimize`, inicia FrankenPHP
├── scripts/                  deploy.sh · backup.sh · restore.sh
└── .env.example · .dockerignore · Makefile
```

## 2. Serviços

| Serviço | Papel | Comando | Volumes | Portas | Observações |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `app` | Servidor web + PHP + Laravel | `frankenphp run` | `db-data`, `app-storage`, `backups`, `caddy-data` | 80/443 (prod) · 8080 (dev) | `restart: unless-stopped`; healthcheck em `/up` |
| `vite` *(dev)* | Servidor de assets com HMR | `npm run dev -- --host` | código-fonte | 5173 | Só em desenvolvimento |
| `mailpit` *(dev)* | Caixa de e-mail de teste | imagem oficial | — | 8025 (UI) | SMTP interno na 1025 |

**Sem banco em container e sem disputas entre múltiplos containers:** o SQLite fica no volume `db-data`, acessado exclusivamente pelo serviço `app`.

## 3. Imagem (Dockerfile multi-stage)

| Estágio | Função |
| :--- | :--- |
| `assets` | Node LTS: `npm ci` e build do Vite (gera `public/build`) |
| `vendor` | Composer: `install --no-dev --optimize-autoloader` |
| `runtime` | Base FrankenPHP com PHP 8.3+; extensões `pdo_sqlite`, `intl`, `gd`, `zip`, `opcache`, `pcntl`; utilitários `sqlite3` (CLI) e `gzip`; copia `vendor` e `public/build`; cria diretórios (`storage`, `/data/db`, `/backups`) e define dono (`chown`) **durante o build** antes de trocar para o usuário não-root; `entrypoint.sh` |

Pontos de atenção:

- **`intl`** é obrigatório (valor por extenso do recibo).
- **DomPDF** usa fontes locais (DejaVu) empacotadas.
- **Permissões sem root:** o `chown` é feito no `Dockerfile` em tempo de build (enquanto ainda é `root`); o `entrypoint.sh` roda já com o usuário da aplicação, apenas garantindo subpastas em `storage/framework/{sessions,cache,views}`, rodando `php artisan optimize` e iniciando o FrankenPHP.
- Em produção, se o usuário não-root precisar abrir as portas 80/443, o `compose.prod.yaml` inclui `cap_add: [NET_BIND_SERVICE]` (ou o FrankenPHP já traz o binário com `setcap cap_net_bind_service=+ep`).

## 4. Docker Compose

### 4.1 Esboço da base (`compose.yaml`)

```yaml
services:
  app:
    build:
      context: .
      dockerfile: docker/Dockerfile
    env_file: .env
    restart: unless-stopped
    command: ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
    volumes:
      - db-data:/data/db
      - app-storage:/var/www/html/storage
      - backups:/backups
      - caddy-data:/data/caddy
    healthcheck:
      test: ["CMD", "curl", "-fsS", "http://localhost:8080/up"]
      interval: 30s
      timeout: 5s
      retries: 3

volumes:
  db-data: {}
  app-storage: {}
  backups: {}
  caddy-data: {}
```

### 4.2 Diferenças em produção (`compose.prod.yaml`, resumo)

- `image:` com tag de versão (ou build local no servidor); `APP_ENV=production`, `APP_DEBUG=false`.
- Publica 80/443 (ou roda atrás de Cloudflare Tunnel / proxy reverso na porta 8080 sem precisar expor portas privilegiadas).
- `cap_add: [NET_BIND_SERVICE]` caso escute diretamente em 80/443 com usuário não-root.
- Rotina diária de backup acionada pelo cron do host (`docker compose exec -T app php artisan backup:run`) e automaticamente antes de cada deploy (`scripts/deploy.sh`).

## 5. Redes

| Rede | Serviços | Regra |
| :--- | :--- | :--- |
| `default` | `app`, `queue`, `scheduler` (+ `vite`, `mailpit` em dev) | Somente `app` publica portas no host. Não há banco nem cache com porta a proteger |

## 6. Volumes

| Volume | Conteúdo | Backup? | Observações |
| :--- | :--- | :---: | :--- |
| `db-data` | `database.sqlite` (+ arquivos `-wal` e `-shm`) | **Sim** (via `VACUUM INTO`, nunca cópia direta) | **Nunca** usar `docker compose down -v` em produção; nunca apontar para sistema de arquivos de rede (NFS, SMB) |
| `app-storage` | Logos e PDFs (`storage/app/private`) | Logos: sim · PDFs: não (regerados a partir do banco) | Privado, fora de `public/` |
| `backups` | Arquivos `.sqlite.gz`, `.tar.gz` de logos | Cópia off-site criptografada | Montado somente leitura no `app` (para diagnósticos) |
| `caddy-data` | Certificados TLS | Não (renováveis) | Só se o TLS for no container |

## 7. Variáveis de ambiente

### 7.1 Aplicação (`.env`)

| Grupo | Variáveis principais |
| :--- | :--- |
| App | `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY`, `APP_TIMEZONE=America/Recife`, `APP_LOCALE=pt_BR`, `APP_FALLBACK_LOCALE=pt_BR` |
| Banco | `DB_CONNECTION=sqlite`, `DB_DATABASE=/data/db/database.sqlite`, `DB_FOREIGN_KEYS=true` |
| Drivers | `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`, `LOG_CHANNEL=stderr` |
| Sessão | `SESSION_LIFETIME=120`, `SESSION_SECURE_COOKIE=true` (produção), `SESSION_SAME_SITE=lax` |
| E-mail | `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` (dev: Mailpit) |
| Domínio | `SERVER_NAME` (FrankenPHP/Caddy), `TRUSTED_PROXIES` (se houver proxy na frente) |
| Backup | `BACKUP_PATH=/backups`, `BACKUP_KEEP_DAYS=14` |

### 7.2 Regras

- `.env` **fora do repositório**; apenas `.env.example` versionado, sem valores reais.
- `APP_KEY` guardada em local seguro fora do servidor.

## 8. Healthchecks

| Serviço | Verificação |
| :--- | :--- |
| `app` | `GET /up` responde 200 (liveness nativo do Laravel) |

## 9. Ambientes: desenvolvimento × produção

| Aspecto | Desenvolvimento | Produção |
| :--- | :--- | :--- |
| Código | *Bind mount* com hot reload | Copiado para a imagem (imutável) |
| Frontend | Vite (HMR) em container próprio | Assets compilados na imagem |
| PHP | OPcache com validação de timestamps | OPcache sem validação; `optimize` no início |
| Debug | `APP_DEBUG=true` | `APP_DEBUG=false` |
| Banco | SQLite em volume nomeado; `migrate:fresh --seed` à vontade | SQLite em volume; migrations só após backup |
| E-mail | Mailpit (UI `:8025`) | SMTP real (**PV-12**) |
| Portas no host | `8080`, `8025`, `5173` | 80/443 (ou 8080 via Cloudflare Tunnel) |
| Dados | Seeders + `DemoDataSeeder` | Dados reais; backup diário |

## 10. Segurança dos containers

- Processos como **usuário não-root**; permissões de diretório preparadas no `Dockerfile`.
- Sem segredos em imagens, Dockerfiles ou histórico git.
- `.dockerignore` rigoroso (sem `.env`, `.git`, `node_modules`, `database/*.sqlite`, `storage/app/*`).

## 11. Deploy e rollback (procedimento enxuto)

Script `scripts/deploy.sh`:

1. **Backup pré-deploy:** executar `docker compose exec -T app php artisan backup:run --label=pre-deploy` (falha = deploy interrompido).
2. Atualizar código/imagem e subir o container (`docker compose -f compose.yaml -f compose.prod.yaml up -d --build`).
3. Executar migrations (`docker compose exec -T app php artisan migrate --force`).
4. *Smoke test:* verificar `GET /up`.
5. **Rollback:** se necessário, voltar para a versão anterior e restaurar o backup pré-deploy (`./scripts/restore.sh <arquivo>`).

## 12. Backup e restauração (enxuto e seguro)

### 12.1 Funcionamento do comando `backup:run`

1. Gera cópia consistente do banco usando `VACUUM INTO '/backups/gestagil-AAAAMMDD-HHMMSS.sqlite'` (seguro mesmo com o SQLite em modo WAL e em uso).
2. Executa `PRAGMA integrity_check` no arquivo gerado para garantir integridade.
3. Compacta com `gzip` (`gestagil-AAAAMMDD-HHMMSS.sqlite.gz`) e remove arquivos com mais de 14 dias (`BACKUP_KEEP_DAYS=14`).
4. Opcionalmente, copia a pasta de logos (`storage/app/private/logos`) e sincroniza com destino externo (ex.: `rclone` ou cópia agendada). PDFs não precisam de backup pois são gerados sob demanda a partir do snapshot no banco.

### 12.2 Procedimento de restauração (`scripts/restore.sh`)

1. Parar o container `app` (`docker compose stop app`).
2. Renomear o banco atual (`database.sqlite`, `-wal` e `-shm`) como cópia de segurança.
3. Descompactar o `.sqlite.gz` escolhido para `/data/db/database.sqlite` garantindo que não restem arquivos `-wal` ou `-shm` antigos.
4. Subir `app` (`docker compose start app`), rodar `php artisan migrate --force` e validar `/up`.

## 13. Comandos úteis

### 13.1 Primeira execução (dev)

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
# acessar http://localhost:8080  (Mailpit em http://localhost:8025)
```

### 13.2 Rotina (dev)

| Objetivo | Comando |
| :--- | :--- |
| Subir / parar | `docker compose up -d` · `docker compose down` (sem `-v`) |
| Logs | `docker compose logs -f app queue` |
| Shell | `docker compose exec app sh` |
| Artisan | `docker compose exec app php artisan <comando>` |
| Migrations / reset | `… artisan migrate` · `… artisan migrate:fresh --seed` |
| Inspecionar o banco | `docker compose exec app sqlite3 /data/db/database.sqlite` ou `artisan db` |
| Testes | `docker compose exec app php artisan test` (Pest) |
| Análise estática / estilo | `… vendor/bin/phpstan analyse` · `vendor/bin/pint` |
| Lint/tipos do frontend | `docker compose exec app npm run lint` · `npm run types` (nomes conforme o kit) |
| Backup manual | `docker compose exec scheduler php artisan backup:run` |

### 13.3 Produção

| Objetivo | Comando |
| :--- | :--- |
| Deploy | `./scripts/deploy.sh <tag>` |
| Status | `docker compose -f compose.yaml -f compose.prod.yaml ps` |
| Backup manual | `./scripts/backup-now.sh` |
| Restauração | `./scripts/restore.sh <arquivo>` (executa os passos do §12.6, com confirmação) |
| Verificar backup | `./scripts/verify-backup.sh` |
| Logs | `docker compose -f … logs --tail=200 -f app` |

### 13.4 Makefile (atalhos propostos)

`make up` · `make down` · `make logs` · `make sh` · `make install` · `make migrate` · `make fresh` · `make test` · `make lint` · `make build-prod` · `make deploy` · `make backup` · `make verify-backup`. **Sem alvo que apague volumes em produção.**

### 13.5 Criação inicial do projeto (Sprint 0)

- Criar o projeto com o instalador oficial do Laravel escolhendo o **starter kit Vue** (ou o repositório oficial do kit) e conferir a documentação vigente; pode ser feito por container temporário, sem instalar PHP/Node na máquina.
- **Alternativa de desenvolvimento:** Laravel Sail (Docker oficial para dev, com opção SQLite). Se você preferir, use o Sail só em desenvolvimento e mantenha o Dockerfile deste plano para produção.

## 14. Hospedagem e SQLite

| Opção | Persistência de disco | Observações |
| :--- | :--- | :--- |
| VPS gratuita/créditos estudantis | Sim | Confirmar condições, limites e prazo dos créditos |
| Máquina própria + túnel (ex.: Cloudflare Tunnel) | Sim | Custo zero; depende de energia/internet; backup off-site é obrigatório |
| PaaS com *filesystem* efêmero | **Não** | **Incompatível com SQLite** sem volume persistente |
| PaaS com volume persistente | Sim | Geralmente pago; verificar |

## 15. Pontos a validar

| PV | Assunto |
| :--- | :--- |
| PV-11 | Onde hospedar com R$ 0, **com disco persistente**; domínio e TLS |
| PV-12 | Provedor SMTP gratuito e domínio de envio |
| PV-25 | Destino externo (Drive/B2/R2) e política de criptografia dos backups |
| PV-28 | Dimensionamento (RAM/CPU/disco) conforme número real de usuários |
