# Makefile Docker Compose : fichier de raccourcis de commandes (fait appel au fichier docker-compose.yml)
COMPOSE=docker compose -f docker-compose.yml

.PHONY: ps start stop restart logs

ps:
	$(COMPOSE) ps

start:
	$(COMPOSE) up -d

stop:
	$(COMPOSE) down

restart: stop start

logs:
	$(COMPOSE) logs -f