#!/usr/bin/env bash
set -e

BACKUP_FILE="$1"
DB_PATH="${2:-/data/db/database.sqlite}"

if [ -z "$BACKUP_FILE" ] || [ ! -f "$BACKUP_FILE" ]; then
    echo "Uso: $0 <caminho-para-backup.sqlite.gz> [caminho-destino-db]"
    exit 1
fi

echo "==> Restaurando backup a partir de: $BACKUP_FILE"
echo "==> Destino do banco: $DB_PATH"

read -p "Tem certeza que deseja restaurar? Isso substituirá o banco atual! (s/N): " CONFIRM
if [ "$CONFIRM" != "s" ] && [ "$CONFIRM" != "S" ] && [ "$CONFIRM" != "y" ] && [ "$CONFIRM" != "Y" ]; then
    echo "Restauração cancelada."
    exit 0
fi

# Backup de segurança do banco ativo atual se existir
if [ -f "$DB_PATH" ]; then
    TIMESTAMP=$(date +%Y%m%d-%H%M%S)
    echo "Arquivando banco atual em ${DB_PATH}.broken-${TIMESTAMP}..."
    cp "$DB_PATH" "${DB_PATH}.broken-${TIMESTAMP}"
    rm -f "${DB_PATH}-wal" "${DB_PATH}-shm"
fi

# Descompacta o arquivo no destino
echo "Descompactando backup em $DB_PATH..."
gzip -dc "$BACKUP_FILE" > "$DB_PATH"

echo "Verificando integridade física do SQLite..."
sqlite3 "$DB_PATH" "PRAGMA integrity_check;"

echo "==> Restauração concluída com sucesso e verificada!"
