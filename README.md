# BioReport

Privacy-first medical records API.

## Requirements

- PHP 8.4
- Composer
- Docker & Docker Compose (for running the stack)
- MongoDB (via Docker or local)

## Setup

**With Docker (recommended):**

```bash
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php -r "file_exists('.env') || copy('.env.example', '.env');"
docker-compose exec app php artisan key:generate
```

Or run setup steps manually; for DB migrations use:

```bash
docker-compose exec app php artisan migrate --force
```

**Without Docker:**

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure `.env` (e.g. `MONGODB_URI`, `MONGODB_DATABASE` for MongoDB).

## Running

- **Web:** Nginx serves the app. With Docker: `http://localhost:8000`
- **Queue (dev):** `docker-compose exec app composer dev` runs the queue worker. For log tailing run `php artisan pail` in another terminal.

## API

- **Base URL:** `http://localhost:8000/api` (with Docker)
- **Health check:** `GET /api/health` — returns HTTP 200 and JSON with `service`, `environment`, `version`, `timestamp` (ISO 8601). No database dependency.

## Commands

| Command | Description |
|--------|-------------|
| `composer test` | Run PHPUnit tests |
| `composer lint` | Fix code style (Pint) |
| `composer lint:test` | Check style without changing files |
| `composer openapi:generate` | Generate OpenAPI spec to `docs/openapi/openapi.json` |

Inside Docker:

```bash
docker-compose exec app composer test
docker-compose exec app composer lint
docker-compose exec app composer openapi:generate
```

## OpenAPI

Generate the OpenAPI specification (JSON) with:

```bash
composer openapi:generate
```

Output: `docs/openapi/openapi.json`. The generated file is gitignored; run the command locally or in CI when needed.

## Git hooks (optional)

A pre-push hook runs tests and linters before `git push`. To install:

```bash
cp scripts/pre-push .git/hooks/pre-push
chmod +x .git/hooks/pre-push
```

Hooks are optional; the project does not install them automatically.

## GitHub templates

- `.github/pull_request_template.md` — use when opening a PR
- `.github/ISSUE_TEMPLATE/bug_report.md` — for bugs
- `.github/ISSUE_TEMPLATE/feature_request.md` — for feature requests

## License

GNU AGPL v3.0 or later. See [LICENSE](LICENSE).
