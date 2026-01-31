# BioReport

Self-Hosted Medical Data Management and AI Analytics System

This project is a privacy-first, self-hosted system designed for centralized storage, analysis, and visualization of personal medical data.

It allows users to fully control their health information without relying on third-party cloud providers.

The system solves common problems related to medical data fragmentation across different laboratories, countries, and formats. It provides a single historical timeline of medical analyses, eliminates manual data entry where possible, and enables clear, structured exports for healthcare professionals.

## Requirements

- PHP 8.4
- Docker & Docker Compose (for running the stack)
- MongoDB (via Docker or local)

## Setup

```bash
docker-compose up -d && docker-compose exec app composer setup
```

## Running

- **Web:** Nginx serves the app. With Docker: `http://localhost:8000`
- **Base URL:** `http://localhost:8000/api`
- **Health check:** `http://localhost:8000/api/health`
- **Database:** MongoDB at `mongodb://mongo:27017` (inside Docker) or `mongodb://localhost:27017` (local)
- **Logs:** `storage/logs/laravel.log`

## Commands

| Command | Description |
|--------|-------------|
| `composer test` | Run PHPUnit tests |
| `composer lint` | Fix code style (Pint) |
| `composer lint:test` | Check style without changing files |
| `composer openapi:generate` | Generate OpenAPI spec to `docs/openapi/openapi.json` |


## Git hooks (optional)

A pre-push hook runs tests and linters before `git push`. To install:

```bash
cp scripts/pre-push .git/hooks/pre-push
chmod +x .git/hooks/pre-push
```

## License

GNU AGPL v3.0 or later. See [LICENSE](LICENSE).
