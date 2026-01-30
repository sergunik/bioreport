# Cursor Coding Rules — BioReport

## 1. Language & Style
- PHP 8.4 only
- Strict typing is mandatory: `declare(strict_types=1);`
- Use readonly properties where possible
- Prefer final classes unless extension is explicitly required
- Use short arrow functions only when readability is preserved

## 2. Architecture Rules
- Controllers:
  - Thin controllers only
  - No business logic
  - Responsible only for:
    - request handling
    - DTO mapping
    - delegating execution to application services
- Requests:
  - Validation only
  - No transformation or business rules
- Services:
  - Contain all business logic
  - Stateless where possible
  - No framework-specific dependencies
  - Pure PHP logic only
  - No magic methods
  - All dependencies injected via constructor DI only
- Repositories:
  - MongoDB access only
  - All query logic must live inside repositories
  - No database access outside repositories
- DTOs:
  - Used for all inputs and outputs
  - Fully typed
  - Immutable

## 3. Typing & Safety
- No mixed types
- No implicit nulls
- All arrays must be replaced with DTOs or value objects
- Use enums instead of string constants
- Validate all external input at system boundaries

## 4. Comments & Documentation
- No inline comments
- Comments allowed ONLY as PHPDoc annotations
- Every public method must have:
  - Clear description
  - No @param, @return, @throws annotation

## 5. Testing
- PHPUnit required
- High coverage expected (business logic ~90%)
- Tests must:
  - Be deterministic
  - Avoid real external API calls
  - Use mocks or fakes where appropriate
- Faker may be used only for test data generation
- One test class per production class
- Use data providers where appropriate
- Use factories for entity instantiation only when it improves test clarity

## 6. Linting & Code Quality
- Code must pass:
  - PHPStan (maximum level)
  - PHP-CS-Fixer (PSR-12 + strict rules)
- No unused imports
- No dead code
- No hidden side effects
- All class usages MUST be explicitly imported via `use`
- Fully qualified class names (FQCN) are strictly forbidden in code
- Global namespace class access (e.g. `\DateTimeImmutable`) is not allowed


## 7. Simplicity Rule
- Prefer simple, explicit solutions
- Avoid over-engineering
- If complexity is required, document the rationale in PHPDoc

## 8. Execution Rules
- All command execution must follow `.cursor/execution.md`
