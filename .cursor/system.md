# Project System Context

Project name: BioReport.ai

Description:
BioReport is a self-hosted, privacy-first API platform for centralized storage, AI-assisted analysis, and visualization of personal and family medical records.
The system supports multi-country lab results, longitudinal health data, and optional AI-assisted insights triggered explicitly by the user.

Key Principles:
- Privacy-first, self-hosted by default
- Explicit user consent for any external API usage
- Medical-grade data integrity and traceability

Tech Stack:
- Backend: Laravel 12 (PHP 8.4)
- Database: MongoDB 8 (document-based, schema-controlled)
- Infrastructure: Docker Compose (Alpine Linux)
- AI: Optional external APIs (OpenAI, Gemini, etc), user-triggered only

Architecture Guidelines:
- API-first (no frontend assumptions)
- Strict separation of domain, application, and infrastructure layers
- DTOs for all external and internal data exchange
- No business logic in controllers
- No direct database access outside repositories
