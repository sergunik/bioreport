COMPOSE=docker-compose
BASE=-f docker-compose.yml
DEV=-f docker-compose.dev.yml

.PHONY: up dev down exec

up:
	$(COMPOSE) $(BASE) up -d

dev:
	$(COMPOSE) $(BASE) $(DEV) up -d

down:
	$(COMPOSE) down

exec:
	$(COMPOSE) exec app sh
