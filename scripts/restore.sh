#!/usr/bin/env bash
set -e

BACKUP_FILE="$1"
DB_PATH="${2:-database/database.sqlite}"

if [ -z "$BACKUP_FILE" ] || [ ! -f "$BACKUP_FILE" ]; then
    echo "Usage: $0 <path-to-backup.sqlite.gz> [destination-db-path]"
    exit 1
fi

echo "==> Restoring backup from: $BACKUP_FILE"
echo "==> Destination: $DB_PATH"

read -p "Are you sure you want to restore? This will replace the current database! (y/N): " CONFIRM
if [ "$CONFIRM" != "y" ] && [ "$CONFIRM" != "Y" ]; then
    echo "Restore cancelled."
    exit 0
fi

# Stop container if running
docker compose stop app 2>/dev/null || true

# Archive current DB if it exists
if [ -f "$DB_PATH" ]; then
    TIMESTAMP=$(date +%Y%m%d-%H%M%S)
    echo "Archiving current DB to ${DB_PATH}.broken-${TIMESTAMP}..."
    mv "$DB_PATH" "${DB_PATH}.broken-${TIMESTAMP}"
    rm -f "${DB_PATH}-wal" "${DB_PATH}-shm"
fi

# Decompress backup to target
echo "Decompressing backup into $DB_PATH..."
gzip -dc "$BACKUP_FILE" > "$DB_PATH"

echo "Running SQLite integrity check..."
sqlite3 "$DB_PATH" "PRAGMA integrity_check;"

# Restart container
docker compose start app 2>/dev/null || true

echo "==> Restore completed successfully!"
