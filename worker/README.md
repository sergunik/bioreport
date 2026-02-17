# PDF Processing Worker

Isolated service that polls `pdf_jobs`, extracts text from PDFs, runs a spaCy-based ML pipeline, and writes `ml_raw_result` and `ml_normalized_result` to the database.

## Requirements

- Python 3.13+
- PostgreSQL 18.x (shared with Laravel app)
- Local storage path for uploaded PDFs (e.g. Laravel `storage/app/uploaded_documents`)

## Environment variables

When run in the main stack (Docker), the worker uses the repo root `.env`. DB connection is taken from `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (mapped to `DB_NAME` / `DB_USER` inside the container). Worker-specific options at the end of `.env`: `WORKER_POLL_INTERVAL_SECONDS`, `WORKER_MAX_ATTEMPTS`, `WORKER_STALE_LOCK_SECONDS`, `WORKER_SPACY_MODEL_NAME`.

For local (non-Docker) runs, set:

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | PostgreSQL host | `localhost` |
| `DB_PORT` | PostgreSQL port | `5432` |
| `DB_NAME` | Database name (use same as `DB_DATABASE`) | `bioreport` |
| `DB_USER` | Database user (use same as `DB_USERNAME`) | `postgres` |
| `DB_PASSWORD` | Database password | (required) |
| `POLL_INTERVAL_SECONDS` | Seconds between polls when no job | `5` |
| `MAX_ATTEMPTS` | Max attempts before marking job failed | `3` |
| `STORAGE_BASE_PATH` | Base path for PDF files (local disk) | `/data/uploaded_documents` |
| `SPACY_MODEL_NAME` | spaCy model to load | `en_core_web_sm` |

## Local development

1. Create a virtualenv and install:

   ```bash
   cd worker
   python -m venv .venv
   source .venv/bin/activate   # or .venv\Scripts\activate on Windows
   pip install -e ".[dev]"
   python -m spacy download en_core_web_sm
   ```

2. Use the repo root `.env` or set `DB_HOST`, `DB_PORT`, `DB_NAME` (= `DB_DATABASE`), `DB_USER` (= `DB_USERNAME`), `DB_PASSWORD`, and `STORAGE_BASE_PATH` (e.g. path to Laravel `storage/app/uploaded_documents`).

3. Run the worker:

   ```bash
   python -m app.main
   ```

4. Lint and type-check:

   ```bash
   ruff check app tests
   mypy app
   ```

5. Tests:

   ```bash
   pytest
   ```

## Docker (main stack)

The worker runs as the `worker` service in the main project’s Docker Compose.

The Compose file is at the repo root; the worker image is built from `docker/worker/Dockerfile`. It uses the same `.env` as the app (DB vars and `WORKER_*` at the end). Uploaded PDFs are in `storage/app/uploaded_documents`, mounted read-only into the container at `/data/uploaded_documents`. No ports are exposed.

## Behaviour

- Polls `pdf_jobs` for `status = 'pending'`.
- Locks one row with `FOR UPDATE SKIP LOCKED`, sets `status = 'processing'`, increments `attempts`, sets `locked_at`.
- Reclaims stale `processing` jobs older than `STALE_LOCK_SECONDS`, so crashed workers do not leave jobs stuck forever.
- Loads the corresponding row from `uploaded_documents`, resolves the PDF path (`STORAGE_BASE_PATH / user_id / uuid.pdf`).
- Extracts text with pdfplumber (text-based PDFs only). Empty or unreadable PDFs are failed or requeued.
- Runs the spaCy pipeline to produce `ml_raw_result`; normalizes to `ml_normalized_result`.
- Updates `uploaded_documents` (`ml_raw_result`, `ml_normalized_result`, `processed_at`) and `pdf_jobs` (`status = 'done'`).
- On error: if `attempts >= MAX_ATTEMPTS` then `status = 'failed'` and `error_message`; otherwise the job is requeued (`status = 'pending'`).
- Handles SIGTERM and exits after the current job (if any).
