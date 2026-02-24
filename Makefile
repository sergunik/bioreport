COMPOSE=docker-compose
BASE=-f docker-compose.yml
DEV=-f docker-compose.dev.yml

.PHONY: up dev setup down exec front

up:
	$(COMPOSE) $(BASE) up -d

dev:
	$(COMPOSE) $(BASE) $(DEV) up -d

setup:
	$(COMPOSE) exec app composer setup

down:
	$(COMPOSE) down

exec:
	$(COMPOSE) exec app sh

front:
	chmod +x ./scripts/fetch-frontend.sh && ./scripts/fetch-frontend.sh
