COMPOSE=docker-compose
BASE=-f docker-compose.yml
DEV=-f docker-compose.dev.yml

.PHONY: up dev setup down exec front worker-lint worker-test

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

worker-lint:
	$(COMPOSE) $(BASE) run --rm --user root -v $(PWD)/worker:/app --entrypoint sh worker -lc "pip install --no-cache-dir -e '.[dev]' && ruff check app tests && mypy app"

worker-test:
	$(COMPOSE) $(BASE) run --rm --user root -v $(PWD)/worker:/app --entrypoint sh worker -lc "pip install --no-cache-dir -e '.[dev]' && pytest tests/unit"
