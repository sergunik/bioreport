# BioReport

Self-Hosted Medical Data Management and AI Analytics System

This project is a privacy-first, self-hosted system designed for centralized storage, analysis, and visualization of personal medical data.

It allows users to fully control their health information without relying on third-party cloud providers.

The system solves common problems related to medical data fragmentation across different laboratories, countries, and formats. It provides a single historical timeline of medical analyses, eliminates manual data entry where possible, and enables clear, structured exports for healthcare professionals.

## Requirements

- Docker & Docker Compose (for running the stack):
  - PHP 8.4
  - PostgreSQL (via Docker or local)

## Setup

```bash
make up && make setup
```

Or:

```bash
docker-compose up -d && docker-compose exec app composer setup
```

This starts nginx, the Laravel app, and PostgreSQL.

## Running

Single-domain deployment: the same origin serves the frontend and the API.

- **Application (SPA):** `http://localhost/`
- **API base:** `http://localhost/api`
- **Health check:** `http://localhost/api/health`
- **Logs:** `storage/logs/laravel.log`

See [docs/self-host.md](docs/self-host.md) for how the frontend is included and how to deploy.

## Commands

| Command              | Description |
|----------------------|-------------|
| `make up`            | Start stack (nginx, app, postgres) |
| `make setup`         | Run migrations and app setup in container |
| `composer test`      | Run PHPUnit tests |
| `composer lint`      | Fix code style (Pint) |
| `composer lint:test` | Check style without changing files |
| `composer api`       | Generate OpenAPI spec to `docs/openapi/openapi.json` |


## Git hooks (optional)

A pre-push hook runs tests and linters before `git push`. To install:

```bash
cp scripts/pre-push .git/hooks/pre-push
chmod +x .git/hooks/pre-push
```

## License

GNU AGPL v3.0 or later. See [LICENSE](LICENSE).
