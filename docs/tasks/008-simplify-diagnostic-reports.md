# Simplify Diagnostic Reports & Observations Architecture

## Goal

Simplify the current data model and creation flow for user lab results to support a fast MVP release.

The main objectives are:
- Reduce architectural complexity
- Make frontend creation flow explicit and predictable
- Keep data model minimal but extensible
- Enforce strict ownership and lifecycle rules


## High-Level Principles

- `observations` is the main data table for analytics and querying
- `diagnostic_reports` is a required logical container for observations
- Every observation MUST belong to a diagnostic report
- No bulk creation logic (one request = one entity)
- All deletions are **hard deletes**
- Favor simplicity over future-proofing at this stage


## Database Schema Changes

### 1. Update `diagnostic_reports` Table

The table should be reduced to a minimal, nominal entity.

**Fields:**
- `id`
- `user_id` (FK, required)
- `notes` (nullable)
- `created_at`
- `updated_at`


### 2. Update `observations` Table

`observations` becomes the primary data source, but remains strictly tied to a report.

**Fields:**
- `id`
- `user_id` (FK, required)
- `diagnostic_report_id` (FK, required)
- `biomarker_name`
- `biomarker_code` (nullable)
- `value` (decimal)
- `unit`
- `reference_range_min` (nullable)
- `reference_range_max` (nullable)
- `reference_unit` (nullable)
- `created_at`
- `updated_at`

**Rules:**
- `diagnostic_report_id` MUST NOT be nullable
- One observation = one measurement


### 3. Referential Integrity Rules

- `diagnostic_reports.user_id` → `users.id`  
  `ON DELETE CASCADE`

- `observations.user_id` → `users.id`  
  `ON DELETE CASCADE`

- `observations.diagnostic_report_id` → `diagnostic_reports.id`  
  `ON DELETE CASCADE`

If a diagnostic report is deleted, **all related observations must be deleted as well**.


## Eloquent Models

### DiagnosticReport
- Independent model
- Own CRUD
- Has many observations

### Observation
- Independent model
- Own CRUD
- Belongs to diagnostic report
- Belongs to user

Models must remain separate. No combined or implicit creation logic.


**Rules:**
- Observations cannot be created without a valid `diagnostic_report_id`
- No bulk or batch endpoints
- Each observation is created via a separate request


## Validation Rules

- `diagnostic_report_id` must exist and belong to the authenticated user
- `user_id` is always derived from authentication context (never from request body)
- One observation represents exactly one measured value


## Deletion Rules

- All deletions are **hard deletes**
- No soft deletes anywhere in this scope
- Deleting a diagnostic report cascades and deletes all its observations
- Deleting an observation deletes only that record


## Out of Scope (Explicitly Deferred)

- Normalized values
- Age/sex-specific reference ranges
- Report types or sources
- PDF/import/integration logic
- Bulk observation creation
- `taken_at` vs `created_at` distinction


## Acceptance Criteria

- Users can create a diagnostic report, then sequentially add observations
- No observation can exist without a diagnostic report
- Data model is minimal and easy to reason about
- Queries by `user_id` and `diagnostic_report_id` are straightforward
- No hidden coupling or implicit behavior between entities

## Tech Stack
- Docker-based deployment
- JWT authentication
- HTTP-only secure cookies for token storage
- PHP 8.4
- Laravel 12
- PostgreSQL 18.1
- Follow all rules from `.cursor/rules.md`
- Strict typing everywhere
- No inline comments
- PHPDoc annotations only
