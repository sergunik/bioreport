# Feature: PDF Upload, Storage, ML Result Persistence, Verification Flow

# 1. Scope

## In Scope

- Secure PDF upload (max 10MB, text-based)
- Storage abstraction (local / S3-ready)
- Database schema for documents and ML results
- File hash deduplication
- Job creation for Python worker (DB queue model)
- Persist raw + normalized ML results
- Secure PDF streaming via Laravel (no public access)
- Split-screen verification support
- JWT authentication (HTTP-only secure cookies)
- Strict typing
- Production-ready structure

## Out of Scope

- Python worker implementation
- ML logic
- AI API mode
- OCR
- Chunking
- Versioning of ML results
- Resource limiting
- Advanced logging
- Background cleanup strategy
- Frontend implementation

# 2. Domain Naming

Avoid generic `files`.

Recommended DB table name: `uploaded_documents`

Rationale:
- Domain-specific
- Future extensibility
- Avoids collision with generic file storage

# 3. Database Schema

## 3.1 uploaded_documents

Represents uploaded PDF + ML result.

```sql
id                    bigint primary key
uuid                  uuid unique not null
user_id               bigint not null
storage_disk          enum('local', 's3') not null
file_size_bytes       bigint not null
mime_type             enum('application/pdf') not null
file_hash_sha256      char(64) not null
ml_raw_result         jsonb null
ml_normalized_result  jsonb null
processed_at          timestamp null
created_at            timestamp not null
updated_at            timestamp not null
````

## 3.2 pdf_jobs

Queue table consumed by Python worker.

```sql
id                      bigint primary key
uploaded_document_id    bigint not null
status                  enum('pending', 'processing', 'done', 'failed') not null default 'pending'
attempts                integer not null default 0
error_message           text null
locked_at               timestamp null
created_at              timestamp not null
updated_at              timestamp not null
```

### Indexes

```sql
index (status)
index (locked_at)
index (uploaded_document_id)
```

# 4. Storage Architecture

## 4.1 Disk Configuration

`config/filesystems.php`

Custom disk:

```
'uploaded_documents' => [
    'driver' => env('UPLOADED_DOCUMENTS_STORAGE_DRIVER', 'local'),
    'root' => storage_path('app/uploaded_documents'),
],
```

Future S3-ready configuration. No implementation of S3 now, but structure allows easy switch.

## 4.2 Storage Usage

Only:

```
Storage::disk('uploaded_documents')
```

No hardcoded paths.

# 5. Upload Flow

## 5.1 Validation Rules

* required
* file
* mimetypes: application/pdf
* max: 10240 (10MB) (move limitation to .env, not hardcoded)

## 5.2 Hashing

Before storing:

```
hash('sha256', file_get_contents(...))
```

## 5.3 Deduplication Logic

If same hash exists for same user:

* Do not reprocess
* Return existing document
* Do not create new job

## 5.4 Storage Strategy

* Generate UUID
* Store as: `{user_id}/{uuid}.pdf`

## 5.5 Job Creation

After successful storage:

* Create uploaded_documents record
* Create pdf_jobs record (status = pending)

Laravel does not call any Python-worker.


# 6. Secure PDF Access

## 6.1 Access Strategy

No public storage exposure.

Route:

```
GET /documents/{uuid}
```

Controller:

* Authorize ownership
* Stream file via `response()->file()`

## 6.2 Authorization Rules

User must:

* Be authenticated
* Own document


# 7 Security Requirements

* JWT-based authentication
* Stored in HTTP-only secure cookies
* SameSite strict
* CSRF protection enabled
* Token refresh strategy implemented

# 8. API Endpoints

## POST /documents

Upload PDF.

Returns:

```
{
  uuid: string,
}
```


## GET /documents

List user documents.


## GET /documents/{uuid}

Stream PDF securely.


## GET /documents/{uuid}/metadata

Return metadata + ML results.


# 9. Strict Typing Requirements

* `declare(strict_types=1);`
* All properties typed
* No inline comments
* Annotate all endpoints, requests, resources, for parsing via Scramble and documentation generation tools
* All public classes and methods documented via PHPDoc
* Domain separation with clear namespaces


# 10. Application Structure

Use clean architecture principles. Separate concerns.
Implement logic in separate domain dir:
- `app/UploadedDocuments`

Use Laravel's service container for dependency injection. No static calls to facades in domain logic.

# 11. Technical Stack

* Docker-based deployment
* PHP 8.4
* Laravel 12
* PostgreSQL 18.1
* JWT auth via secure HTTP-only cookies
* Strict typing everywhere
* Clean architecture separation
* Cursor rules compliance


# 12. Required Tests

## 12.1 Feature Tests

## 12.2 Unit Tests

## 12.3 Integration Tests

# 13. Acceptance Criteria (AC)

* User can upload PDF ≤10MB
* Duplicate upload does not create new processing job
* Document stored securely
* PDF inaccessible without authentication
* Document stream works only for owner
* Processing statuses transition correctly
* DB indexes created as specified
* Strict typing enforced
* No inline comments
* All public classes documented via PHPDoc
* Docker build succeeds
* All tests pass


# 14. Future-Ready Considerations (No Implementation Now)

* S3 storage switch
* PDF files rotation and cleanup strategy
* Audit logging
* Rate limiting
* Observability
