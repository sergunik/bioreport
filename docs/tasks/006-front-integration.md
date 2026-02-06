# Task 006 — Self-Hosted Packaging: Unified Backend + Prebuilt Frontend

## Purpose

The purpose of this task is to design and implement a **secure, offline-capable, self-hosted distribution model** for the BioReport project, where:

- Backend and frontend are delivered as a **single runtime unit**
- Frontend is included as **prebuilt static assets**, not as source code
- End users can deploy the system with **minimal technical knowledge**
- No external services, cloud dependencies, or online build steps are required at runtime

This task explicitly optimizes for:
- Security-first architecture
- Offline usage
- Low cognitive load for self-host users
- Clean separation of build-time and runtime concerns



## Goal

Deliver a **production-ready self-host setup** where:
- The backend is the only runtime service
- The frontend is served as static files by the backend
- No Node.js, React, or frontend tooling exists in the runtime environment
- The entire system is accessible under a single domain:
  - `/` → frontend (SPA)
  - `/api/*` → backend API



## Core Principles

1. **Build-time separation, runtime unification**
2. **One domain, one entry point**
3. **Zero frontend runtime dependencies**
4. **Offline-first by default**
5. **Secure-by-default configuration**
6. **Minimal setup steps for end users**



## Architecture Overview

### Repositories

- `frontend-repo`
  - Contains frontend source code (React / SPA)
  - Used only at build time
  - Not required for self-host runtime
  - https://github.com/sergunik/bioreport-canvas.git

- `backend-repo`
  - Laravel application
  - Docker-based runtime
  - Final self-host artifact
  - Includes prebuilt frontend assets
  - https://github.com/sergunik/bioreport.git



## Frontend Handling Strategy

### Build Phase (CI/CD only)

- Frontend is built using its own CI pipeline
- Output is a static bundle:
  - `index.html`
  - JS/CSS assets
- The build result is exported as an **artifact**

### Runtime Phase (Self-Host)

- Frontend source code is **not included**
- Only static assets are present
- Assets are placed into: `frontend/public/`
- Backend serves frontend directly

No Node.js or frontend tooling exists in production containers.



## Backend Responsibilities

- Serve frontend static files
- Expose API under `/api/*`
- Handle authentication and authorization
- Manage database access
- Enforce security constraints
- Act as the single runtime service



## Authentication Model

- JWT-based authentication
- Tokens stored in **HTTP-only, secure cookies**
- No access tokens stored in localStorage or sessionStorage
- Frontend never handles raw tokens directly
- Backend fully controls auth lifecycle



## Deployment Model

- Docker-based self-host deployment
- Single `docker-compose.yml` for production
- No exposed ports except via reverse proxy (if used)
- Designed to work without Traefik, but compatible with it

Target user should be able to deploy via: `docker compose up -d`



## Update Strategy (Non-blocking)

* Frontend and backend have independent release cycles
* Frontend updates are delivered as new static asset bundles
* Backend releases may optionally include updated frontend builds
* Update automation is explicitly **out of scope** for this task



## Domain & Routing Expectations

* Single domain (e.g. `bioreport.local`)
* Routing rules:

    * `/` → SPA frontend
    * `/api/*` → Laravel backend
* SPA fallback routing must be supported



## Security Considerations

* No public API exposure by default
* No unnecessary open ports
* API accessible only via same-origin requests
* Environment-based configuration
* Secure cookie flags enforced
* Production-ready defaults



## User Experience Goals (Self-Host)

Target user profile:

* Non-developer or basic technical user
* Running a home lab or personal server
* Wants privacy, control, and offline access

UX priorities:

* Minimal setup instructions
* No frontend build steps
* No dependency juggling
* Clear and predictable behavior



## Tech Stack

* Docker-based deployment
* JWT authentication
* HTTP-only secure cookies for token storage
* PHP 8.4
* Laravel 12
* PostgreSQL 18.1
* Follow all rules from `.cursor/rules.md`
* Strict typing everywhere
* No inline comments
* PHPDoc annotations only



## Deliverables

1. Backend repository structure supporting static frontend delivery
2. Clear separation between frontend build artifacts and backend runtime
3. Docker configuration suitable for self-host usage
4. Documentation explaining:

    * How frontend is included
    * How users deploy the system
    * What is and is not customizable



## Non-Goals

* No SaaS deployment
* No standalone frontend deployment
* No runtime frontend builds
* No multi-domain setup
* No cloud-only assumptions



## Success Criteria

This task is considered complete when:

* A user can self-host the system without touching frontend tooling
* The system works offline
* The frontend and backend function correctly under a single domain
* Security defaults are enforced without user intervention
* The architecture remains clean, predictable, and maintainable
