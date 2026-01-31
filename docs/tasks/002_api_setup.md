# Task 002 — API Setup & Tooling

## Goal
Set up the initial API infrastructure, including a health check endpoint,
automated OpenAPI documentation generation, basic tests, and repository-level
development tooling (GitHub templates and hooks).

## Scope

### Included
- API base routing setup
- Public health check endpoint
- OpenAPI documentation generation
- PHPUnit tests for the health endpoint
- Update README with API and tooling information
- GitHub repository templates (PR, issues)
- Git hooks for basic quality checks

### Excluded
- Authentication or authorization
- Business logic
- Domain models
- Any medical data handling

---

## Functional Requirements

### API
- Create base API route group (`/api`)
- Implement a public `GET /api/health` endpoint
- Health check response must include:
  - service name
  - environment
  - application version
  - timestamp (ISO 8601)
- No database dependency in health check

### OpenAPI / Documentation
- OpenAPI 3.2.0 specification must be generated automatically
- Documentation must be generated from code annotations or attributes
- Provide a CLI command to generate OpenAPI spec
- Output format: YAML or JSON (explicitly chosen)
- Generated file must be stored in a dedicated directory (e.g. `/docs/openapi`)

### Testing
- PHPUnit test for health endpoint
- Test must:
  - Assert HTTP status
  - Assert response schema
  - Be deterministic
- No external services or containers required during test execution

---

## Tooling & Dev Experience

### README
Update `README.md` with:
- API base URL
- Health check endpoint description
- How to run tests
- How to generate OpenAPI documentation
- Required Docker commands (if applicable)

### GitHub Templates
Add the following files:
- `.github/pull_request_template.md`
- `.github/ISSUE_TEMPLATE/bug_report.md`
- `.github/ISSUE_TEMPLATE/feature_request.md`

Templates must:
- Enforce clear descriptions
- Include testing and documentation checklists

### Git Hooks
- Add a pre-push or pre-commit hook
- Hook must run:
  - tests
  - linters
- Hooks must be optional and documented (no hard blocking)

---

## Technical Constraints
- PHP 8.4
- Laravel 12
- Follow all rules from `.cursor/rules.md`
- Strict typing everywhere
- No inline comments
- PHPDoc annotations only

---

## Acceptance Criteria
- [ ] `/api/health` returns expected JSON structure
- [ ] Health endpoint covered by PHPUnit test
- [ ] OpenAPI spec can be generated via a single command
- [ ] OpenAPI file is committed or explicitly gitignored (documented choice)
- [ ] README updated with all relevant instructions
- [ ] GitHub templates present and usable
- [ ] Git hooks documented and working

---

## Notes
- Prefer the simplest possible OpenAPI solution that integrates cleanly with Laravel
- Avoid premature abstraction
- Treat this task as API foundation, not feature development
