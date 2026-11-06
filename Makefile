.PHONY: up down build logs test lint backup clean help

help:
	@echo "GestAgil - Comandos úteis:"
	@echo "  make up          - Sobe os containers de desenvolvimento"
	@echo "  make down        - Para os containers"
	@echo "  make logs        - Exibe os logs do container app"
	@echo "  make test        - Executa os testes automatizados (Pest)"
	@echo "  make lint        - Roda Pint e verificação de tipos"
	@echo "  make backup      - Executa backup imediato do SQLite"
	@echo "  make build       - Compila assets frontend"

up:
	docker compose up -d

down:
	docker compose down

logs:
	docker compose logs -f app

test:
	php artisan test

lint:
	./vendor/bin/pint --test
	npm run build

backup:
	php artisan backup:run

build:
	npm run build
