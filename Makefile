COMPOSE=docker compose -f docker-compose.yml
APP_PORT=8001

.PHONY: help install start stop restart ps logs serve dev db-create db-drop migrate fixtures db-reset validate test front-dev front-build qa

help:
	@echo "SecureFlow developer commands"
	@echo ""
	@echo "Setup:"
	@echo "  make install       Install PHP and JS dependencies"
	@echo "  make start         Start Docker services"
	@echo "  make db-create     Create database if needed"
	@echo "  make migrate       Run Doctrine migrations"
	@echo "  make fixtures      Load development fixtures"
	@echo "  make db-reset      Drop, recreate, migrate and load fixtures"
	@echo ""
	@echo "Development:"
	@echo "  make serve         Start Symfony local server on port $(APP_PORT)"
	@echo "  make front-dev     Start Vite dev server"
	@echo "  make dev           Start Docker services and Symfony server"
	@echo ""
	@echo "Quality:"
	@echo "  make validate      Validate Doctrine schema and Symfony container"
	@echo "  make test          Run PHPUnit"
	@echo "  make front-build   Build React/Vite assets"
	@echo "  make qa            Run backend tests and frontend build"
	@echo ""
	@echo "Docker:"
	@echo "  make ps            Show Docker services"
	@echo "  make logs          Follow Docker logs"
	@echo "  make stop          Stop Docker services"
	@echo "  make restart       Restart Docker services"

install:
	composer install
	npm install

start:
	$(COMPOSE) up -d

stop:
	$(COMPOSE) down

restart: stop start

ps:
	$(COMPOSE) ps

logs:
	$(COMPOSE) logs -f

serve:
	symfony serve -d --port=$(APP_PORT)

dev: start serve
	@echo "Backend is available on http://127.0.0.1:$(APP_PORT)"
	@echo "Run 'make front-dev' in another terminal for the React dashboard."

db-create:
	php bin/console doctrine:database:create --if-not-exists

db-drop:
	php bin/console doctrine:database:drop --force --if-exists

migrate:
	php bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	php bin/console doctrine:fixtures:load --no-interaction

db-reset: db-drop db-create migrate fixtures

validate:
	php bin/console doctrine:schema:validate
	php bin/console lint:container

test:
	php bin/phpunit

front-dev:
	npm run dev

front-build:
	npm run build

qa: validate test front-build phpstan

.PHONY: phpstan
phpstan:
	mkdir -p var/cache/dev
	php bin/console debug:container --env=dev --format=xml > var/cache/dev/phpstan-container.xml
	vendor/bin/phpstan analyse --memory-limit=1G
