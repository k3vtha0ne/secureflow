# Makefile Docker Compose : fichier de raccourcis de commandes (fait appel au fichier docker-compose.yml)
COMPOSE=docker compose -f docker-compose.yml
APP_PORT=8001

.PHONY: ps start stop restart logs serve

ps:
	$(COMPOSE) ps

start:
	$(COMPOSE) up -d

serve:
	symfony serve -d --port=$(APP_PORT)

stop:
	$(COMPOSE) down

restart: stop start

logs:
	$(COMPOSE) logs -f

