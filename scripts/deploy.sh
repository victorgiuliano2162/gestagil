#!/usr/bin/env bash
set -e

TAG="${1:-latest}"
echo "==> Deploying GestAgil (tag: ${TAG})..."

# 1. Run pre-deploy backup
echo "==> Running pre-deploy backup..."
docker compose exec -T app php artisan backup:run --label=pre-deploy || {
    echo "ERROR: Pre-deploy backup failed! Aborting deploy."
    exit 1
}

# 2. Build / Update containers
echo "==> Rebuilding and restarting containers..."
docker compose -f compose.yaml -f compose.prod.yaml up -d --build

# 3. Run database migrations
echo "==> Running migrations..."
docker compose exec -T app php artisan migrate --force

# 4. Clear and optimize framework cache
echo "==> Optimizing cache..."
docker compose exec -T app php artisan optimize

# 5. Smoke test health endpoint
echo "==> Checking healthcheck (/up)..."
curl -fsS http://localhost:8080/up || {
    echo "WARNING: Healthcheck did not respond with 200!"
}

echo "==> Deploy completed successfully!"
