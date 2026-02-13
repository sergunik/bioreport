# Backend Task Plan — User Settings & Account Management API

## Goal

Extend the existing API to support user settings pages:

- Profile Settings
- Security Settings
- Danger Zone

Implement clean, well-structured endpoints for managing the authenticated user via `/me`, with clear separation of concerns:

- ProfileController
- SecurityController
- PrivacyController

Do not modify or refactor unrelated parts of the system.

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

# 1. Database Changes

## 1.1 Users Table

Ensure the `users` table supports hard deletion without integrity issues.

No schema changes required unless validation rules require adjustments.

## 1.2 Accounts Table

Only the following fields are editable through Profile Settings:

- `nickname`
- `language`
- `timezone`

No additional columns required for this task.

# 2. API Design

All new routes must be protected by:

```php
Route::middleware('auth:jwt')
````

Base structure:

```
GET     /me
PATCH   /me
DELETE  /me
```

Additional security endpoints under:

```
/me/security
```
There will be one PATCH endpoints for updating email and password.


# Privacy API

## DELETE /me

Implements hard deletion of user.

Requirements:

* Require current password in request body
* Validate password before deletion
* Use DB transaction
* Delete user
* Ensure cascading deletes remove:

    * account
    * diagnostic reports
    * observations
    * etc

After deletion:

* Invalidate JWT cookie
* Return 204 No Content


# Controllers Structure

Create:

* `ProfileController`
* `SecurityController`
* `PrivacyController`

Do not reuse `AccountController`.

# Non-Goals (Out of Scope)

Do not implement:

* 2FA
* Email verification
* Audit logs
* Encryption at rest
* Data export
* Multi-profile support
* Soft deletes
* GDPR anonymization
* Subscription logic
* Multi-tenant refactor


## Expected Outcome

After completion:

* Frontend can fully power Profile, Security and Danger Zone pages.
* API is cleanly structured.
* Auth logic and profile logic are separated.
* Hard deletion is safe and confirmed by password.
* Code follows strict project rules and Laravel best practices.
