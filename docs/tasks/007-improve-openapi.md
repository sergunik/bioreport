# Task: Enhance OpenAPI Specification with Comprehensive Metadata and Examples

## Objective
Review the existing backend codebase and enrich the automatically generated `openapi.json` by adding appropriate annotations, decorators, or configuration so that the resulting OpenAPI document becomes as detailed, self-documenting, and developer-friendly as possible.

## Scope
Focus on improving the following aspects of the OpenAPI specification:

### 1. **Operation-Level Details**
- Add clear, concise `summary` and descriptive `description` for every endpoint.
- Include realistic `examples` for both request bodies and query/path parameters where applicable.
- Specify meaningful `operationId`s (if not auto-generated well) to improve client SDK generation.

### 2. **Request/Response Modeling**
- Ensure all request and response schemas use precise data types (`string`, `integer`, `boolean`, enums, etc.) with proper validation constraints (`minLength`, `maxLength`, `pattern`, `minimum`, `maximum`, `format`, etc.).
- Provide multiple `examples` per schema or media type using the `examples` keyword (not just `example`) to illustrate common and edge-case scenarios.
- Use `$ref` consistently to avoid duplication and improve maintainability.

### 3. **Error Responses**
- Document all possible HTTP status codes (e.g., `400`, `401`, `403`, `404`, `422`, `500`) with:
  - Clear descriptions of when each occurs.
  - Example error payloads matching your actual error format (e.g., `{ "error": "Invalid email format" }`).

### 4. **Security Schemes**
- Explicitly define and reference security schemes (`Bearer`, `ApiKey`, `OAuth2`, etc.) at the operation or global level.
- Annotate which endpoints require authentication and which are public.

### 5. **Global Metadata**
- Fill out top-level fields: `title`, `description`, `version`, `contact`, and `license`.
- Add a rich `description` supporting Markdown for better rendering in Swagger UI / Redoc.

## Implementation Guidance
- Use framework-specific decorators (e.g., `@ApiProperty()`, `@ApiOperation()`, `@ApiResponse()` in NestJS; `@extend_schema` in Django DRF; `@openapi` comments in FastAPI) to inject metadata directly into route handlers or DTOs.
- Avoid hardcoding examples in strings—use real objects that can be serialized.
- Validate the final `openapi.json` using tools like [Swagger Editor](https://editor.swagger.io/) or `swagger-cli validate`.

## Acceptance Criteria
The generated `openapi.json` includes:
- Descriptive summaries and explanations for all endpoints.
- Realistic request/response examples covering success and error cases.
- Full error response documentation with example payloads.
- Properly typed and constrained schemas.
- Complete security declarations.
- Accurate server definitions and global metadata.

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
