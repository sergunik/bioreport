# 004 – Account API Specification

## Overview

This document defines the **Account** domain for **BioReport.ai**.

An Account represents non-medical, stable identity and preference data of a user.
Each user owns **exactly one account**.

Account data is strictly separated from:
- authentication data
- medical data
- family profiles

## Tech Stack

- Docker-based deployment
- JWT authentication
- HTTP-only secure cookies for token storage
- PHP 8.4
- Laravel 12
- Follow all rules from `.cursor/rules.md`
- Strict typing everywhere
- No inline comments
- PHPDoc annotations only

## Scope

### Included
- Automatic account creation on user registration
- Read current account
- Update mutable account fields
- Hard delete account (with logout)
- Strict ownership and invariants

### Explicitly Excluded
- Medical data
- Family members / profiles
- Soft delete
- Cascade deletion implementation
- Audit log
- Avatars or file storage
- Roles or permissions

## Core Invariants

- 1 user → 1 account
- Account cannot exist without a user
- Account is created immediately after user creation
- `sex` and `date_of_birth` are required
- `date_of_birth` is immutable after creation
- Deleting an account deletes the user
- Failure to create an account is a fatal system error

## Data Model

### Users (existing)

users
- id (uuid, primary key)
- email
- password
- created_at
- updated_at

### Accounts

accounts
- id (uuid, primary key)
- user_id (uuid, unique, foreign key -> users.id)
- nickname (string, nullable)
- date_of_birth (date, NOT NULL, immutable)
- sex (enum: male, female, NOT NULL)
- language (string, ISO-639-1, default: "en")
- timezone (string, IANA timezone, default: "UTC")
- updated_at

### Field Rules

| Field         | Rules                                        |
| ------------- | -------------------------------------------- |
| nickname      | Optional, non-unique, freely changeable      |
| sex           | Required, enum (`male`, `female`), immutable |
| date_of_birth | Required, immutable after creation           |
| language      | Mutable                                      |
| timezone      | Mutable                                      |


## Account Creation

### When

* Automatically during `POST /api/auth/register`

### How

* No database transaction
* Linear execution:
create user
create account
if account creation fails → throw error and abort request.

Account creation failure indicates a critical system inconsistency and must not be silently recovered.

### Default Values

* nickname: null
* language: "en"
* timezone: "UTC"

## API Endpoints

Base path: `/api/account`

Authentication required for all endpoints.


### Get Current Account

`GET /api/account`

Returns the account associated with the authenticated user.

#### Response

```json
{
  "id": "uuid",
  "nickname": "optional string",
  "date_of_birth": "YYYY-MM-DD",
  "sex": "male",
  "language": "en",
  "timezone": "UTC"
}
```

---

### Update Account

`PATCH /api/account`

Partially updates mutable account fields.

#### Allowed Fields

* nickname
* language
* timezone

#### Forbidden Fields

* user_id
* sex
* date_of_birth

Attempts to update forbidden fields must return a validation error.

#### Request Example

```json
{
  "nickname": "Adam",
  "language": "uk",
  "timezone": "Europe/Kyiv"
}
```

#### Response

```json
{
  "status": "updated"
}
```

---

### Delete Account

`DELETE /api/account`

Permanently deletes the account and the associated user.

#### Behavior

1. Delete account
2. Delete user
3. Revoke refresh token
4. Clear authentication cookies
5. Force logout

#### Notes

TODO:
Cascade deletion of medical and related data
To be implemented later via background job

#### Response

```json
{
  "status": "account_deleted"
}
```

---

## Security & Ownership

* Account is always resolved from the authenticated user
* No account IDs are exposed in URLs
* Access to other users' accounts is impossible
* Account cannot be recreated or duplicated

---

## Error Handling

* Missing account for an existing user is a **500 Internal Server Error**
* Invalid field updates return **422 Validation Error**
* Unauthorized access returns **401 Unauthorized**

---

## Non-Goals (Current Phase)

* Email verification
* Multi-account support
* Multi-device sessions
* Role-based access control
* Account recovery

---

## Summary

The Account API provides a minimal, strict identity layer required for medical data management.
It intentionally avoids flexibility in favor of data integrity, predictability, and privacy.
