# 003 – Authentication API Specification

## Overview

This document describes the authentication subsystem.

The system is:
- API-only (no sessions, no Blade views)
- Stateless authentication using JWT
- Self-hosted, privacy-first
- Designed for future extensibility without premature complexity

Frontend communicates with backend exclusively via JSON API.

---

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

---

## Authentication Model

### Token Strategy

- **Access Token**
  - JWT
  - Short-lived (5–15 minutes)
  - Used for authenticated API requests

- **Refresh Token**
  - One active refresh token per user
  - Long-lived (e.g. 14 days)
  - Stored **hashed** in the database
  - Used to rotate access tokens
  - Revoked on logout or password reset

### Token Storage (Frontend)

- Tokens are stored in **HTTP-only secure cookies**
- No access via JavaScript
- CSRF protection is required

---

## User Identity

### Users Table

- Primary key: **UUID**
- Email is unique
- No email verification at this stage

```text
users
- id (uuid, primary key)
- email (string, unique)
- password (string)
- created_at
- updated_at
````

### Refresh Tokens Table

```text
refresh_tokens
- id (uuid, primary key)
- user_id (uuid, foreign key)
- token_hash (string)
- expires_at (timestamp)
- revoked_at (timestamp, nullable)
- created_at
```

---

## Password Security

* Salted password hashing uses **Argon2id**
* Parameters configured for maximum reasonable security
* Password requirements:

  * Minimum length: 12 characters
  * Must contain mixed character types

---

## API Endpoints

Base path: `/api/auth`

---

### Register

`POST /api/auth/register`

Registers a new user and immediately authenticates them.

#### Request

```json
{
  "email": "user@example.com",
  "password": "strongpassword123!"
}
```

#### Behavior

* Validate input
* Hash password using Argon2id
* Create user
* Issue access token
* Issue refresh token
* Set tokens as HTTP-only cookies

#### Response

```json
{
  "user": {
    "id": "uuid",
    "email": "user@example.com"
  }
}
```

---

### Login

`POST /api/auth/login`

Authenticates an existing user.

#### Request

```json
{
  "email": "user@example.com",
  "password": "strongpassword123!"
}
```

#### Behavior

* Validate credentials
* Revoke existing refresh token (if any)
* Issue new access token
* Issue new refresh token
* Set tokens as HTTP-only cookies

#### Response

```json
{
  "user": {
    "id": "uuid",
    "email": "user@example.com"
  }
}
```

---

### Refresh Token

`POST /api/auth/refresh`

Rotates the access token using the refresh token.

#### Behavior

* Validate refresh token
* Ensure:

  * Not revoked
  * Not expired
* Revoke old refresh token
* Issue new refresh token
* Issue new access token
* Update cookies

#### Response

```json
{
  "status": "ok"
}
```

---

### Logout

`POST /api/auth/logout`

Logs out the current user.

#### Behavior

* Revoke current refresh token
* Clear authentication cookies
* Does NOT revoke tokens for other devices (single-device system)

#### Response

```json
{
  "status": "logged_out"
}
```

---

## Password Reset

### Forgot Password

`POST /api/auth/password/forgot`

Initiates password reset.

#### Request

```json
{
  "email": "user@example.com"
}
```

#### Behavior

* Generate password reset token
* Send reset email
* Always return generic success response
* Do not reveal whether the email exists

#### Response

```json
{
  "status": "ok"
}
```

---

### Reset Password

`POST /api/auth/password/reset`

Resets the password and automatically logs the user in.

#### Request

```json
{
  "token": "reset-token",
  "password": "newstrongpassword123!"
}
```

#### Behavior

* Validate reset token
* Update password (Argon2id)
* Revoke all existing refresh tokens
* Issue new access token
* Issue new refresh token
* Auto-login user

#### Response

```json
{
  "user": {
    "id": "uuid",
    "email": "user@example.com"
  }
}
```

---

## Rate Limiting

Applied via Laravel throttle middleware:

* Login attempts
* Password reset requests
* Token refresh

Recommended limits:

* Login: 5 attempts / minute
* Password reset: 3 requests / 10 minutes

---

## Code Structure

Auth-related code lives under:

```text
app/
 └─ Auth/
     ├─ Controllers/
     ├─ Requests/
     ├─ Services/
     ├─ DTOs/
     ├─ Actions/
     └─ Guards/
```

No module system is used.

---

## Explicit Non-Goals (Current Phase)

* Email verification
* OAuth / social login
* Multi-device session management
* MFA / 2FA
* Role-based authorization

---

## Notes

* AI components **never** participate in authentication logic
* Auth system is strictly infrastructure-level
* All decisions favor predictability, auditability, and privacy


