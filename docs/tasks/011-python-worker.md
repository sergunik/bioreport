# Python PDF Processing Worker – Technical Implementation Plan

## 1. Purpose

The Python worker is an isolated service responsible for:

- Polling `pdf_jobs` table
- Locking a pending job safely
- Fetching corresponding PDF file
- Extracting text from text-based PDF
- Running ML model (spaCy-based)
- Producing:
    - `ml_raw_result`
    - `ml_normalized_result`
- Updating:
    - `uploaded_documents`
    - `pdf_jobs`
- Handling retries & failure states safely

The worker **does not know anything** about Laravel internals. And don't need to parse the entire laravel project.

It only interacts with:

- PostgreSQL
- File storage (local mode only in current scope)


# 2. Scope

## 2.1 In Scope

- PostgreSQL polling mechanism
- Safe DB-level locking
- Text-based PDF parsing
- spaCy inference
- Raw + normalized result generation
- Updating database
- Retry mechanism
- Dockerized deployment
- Fully covered by tests
- Structured logging
- Production-ready architecture

## 2.2 Out of Scope

- API-mode
- S3 integration
- OCR (scanned PDFs)
- Model training
- Monitoring systems
- Horizontal scaling strategy
- Rate limiting
- Chunking
- Advanced observability



# 3. Technology Stack (Latest Stable)

- Python 3.13
- PostgreSQL 18.x client
- psycopg 3.x
- SQLAlchemy 2.x (Core, not ORM-heavy)
- spaCy 3.8+
- pdfplumber 0.11+
- pydantic 2.x
- structlog 24.x
- pytest 8.x
- pytest-asyncio 0.23+
- testcontainers 4.x
- ruff (linting)
- mypy (strict mode)
- Docker (latest)
- docker-compose v2


# 4. High-Level Architecture

```

worker/
├── app/
│   ├── config.py
│   ├── db.py
│   ├── models.py
│   ├── repository.py
│   ├── job_locking.py
│   ├── pdf_reader.py
│   ├── ml_pipeline.py
│   ├── normalizer.py
│   ├── processor.py
│   ├── main.py
│   └── exceptions.py
├── tests/
├── Dockerfile
├── docker-compose.yml
├── pyproject.toml
└── README.md

````



# 5. Database Contract (Strict)

## 5.1 uploaded_documents

```sql
id bigint
uuid uuid
user_id bigint
storage_disk enum('local','s3')
file_size_bytes bigint
mime_type enum('application/pdf')
file_hash_sha256 char(64)
ml_raw_result jsonb nullable
ml_normalized_result jsonb nullable
processed_at timestamp nullable
created_at timestamp
updated_at timestamp
````

Worker only reads:

* id
* storage_disk

Worker updates:

* ml_raw_result
* ml_normalized_result
* processed_at


## 5.2 pdf_jobs

```sql
id bigint
uploaded_document_id bigint
status enum('pending','processing','done','failed')
attempts int
error_message text nullable
locked_at timestamp nullable
created_at timestamp
updated_at timestamp
```

Worker updates:

* status
* attempts
* error_message
* locked_at



# 6. Concurrency & Locking Strategy

Use PostgreSQL row-level locking:

```
SELECT *
FROM pdf_jobs
WHERE status = 'pending'
ORDER BY id
FOR UPDATE SKIP LOCKED
LIMIT 1;
```

Inside transaction:

1. Lock row
2. Update:

    * status = 'processing'
    * locked_at = now()
    * attempts += 1
3. Commit
4. Process outside transaction

If crash happens before commit → row remains pending.



# 7. Processing Flow

## 7.1 Main Loop

1. Poll every N seconds
2. Fetch 1 job
3. Lock job
4. Fetch corresponding document
5. Resolve file path
6. Extract text
7. Run ML
8. Normalize output
9. Update DB in single transaction:

    * uploaded_documents
    * pdf_jobs → done
10. Continue loop



# 8. PDF Handling

* Only text-based PDFs
* Use pdfplumber
* Extract per page
* Join into single string

If text is empty → mark failed



# 9. ML Pipeline

## 9.1 Raw Result Format

JSON.

## 9.2 Normalized Result

```json
{
  "first_name": "John",
  "second_name": "Dow",
  "DOB": "01.01.2000",
  "language": "en",
  "common": "long text here",
  "observations": [
    {
      "biomarker_name": "Hemoglobin",
      "biomarker_code": "HB",
      "value": 13.4,
      "unit": "g/dL",
      "reference_range": "12-16"
    }
  ]
}
```



# 10. Error Handling

### Retry Rules

* attempts < 3 → retry
* attempts >= 3 → failed

On exception:

```
status = 'failed'
error_message = truncated stack
```



# 11. Logging

Use structlog JSON logs:

Fields:

* job_id
* document_id
* duration_ms
* status
* error_type

No print statements.



# 12. Configuration

Environment variables:

```
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASSWORD
POLL_INTERVAL_SECONDS
MAX_ATTEMPTS
STORAGE_BASE_PATH
SPACY_MODEL_NAME
```

Use pydantic Settings.

---

# 13. Dockerfile



# 14. docker-compose.yml

Use variables from `.env`.
Do not expose ports.



# 15. Testing Strategy

## 15.1 Unit Tests

* pdf_reader
* ml_pipeline
* normalizer
* retry logic
* locking logic

Coverage ≥ 95%



## 15.2 Integration Tests

Using testcontainers:

* Spin up real PostgreSQL
* Insert fake job
* Insert fake PDF
* Run processor once
* Assert:

    * status changed
    * JSON stored
    * processed_at not null



## 15.3 Failure Tests

* Broken PDF
* Empty PDF
* DB disconnect
* ML exception
* Exceed max attempts



# 16. Acceptance Criteria (AC)

### AC-1

Worker locks only one job at a time using SKIP LOCKED.

### AC-2

If worker crashes mid-processing, job becomes retryable.

### AC-3

ML results are saved in correct JSON structure.

### AC-4

Normalized output is non-null when raw exists.

### AC-5

After success:

* pdf_jobs.status = done
* uploaded_documents.processed_at != null

### AC-6

After 3 failures:

* status = failed

### AC-7

Worker is fully Dockerized.

### AC-8

Test coverage ≥ 95%.

### AC-9

All code passes:

* ruff
* mypy --strict
* pytest



# 17. Security Considerations

* No direct filesystem traversal
* Validate file exists before processing
* Do not log PDF content
* Do not log full ML results



# 18. Future-Ready Design Decisions

* Repository pattern
* ML abstraction layer
* Storage abstraction placeholder
* Config-driven model loading
* Easy horizontal scaling via multiple workers



# 19. Non-Functional Requirements

* Memory usage < 512MB typical
* Single job processing time < 5 seconds (text-based)
* Graceful SIGTERM shutdown
* Idempotent processing



# 20. Deliverables

* Complete worker repository
* Dockerfile
* docker-compose
* CI config
* README with setup instructions
* Fully passing test suite
* Production-ready codebase
