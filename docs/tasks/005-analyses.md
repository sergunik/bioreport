# 005-diagnostics-crud.md

## Diagnostic Reports & Observations CRUD System
Flexible lab results management with biomarker standardization foundation

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

## Goal
Implement a robust, future-proof CRUD system for medical diagnostic reports and observations (lab results) with:
- Strict ownership enforcement (user-scoped data)
- Biomarker comparison over time via standardized codes
- Dual-value storage (original + normalized) for charting
- PostgreSQL JSONB optimization
- Laravel 12 strict typing compliance
- Foundation for PDF parsing & lab integrations

---

## Core Domain Concepts

| Concept | Entity | Description | Key Constraints |
|--------|--------|-------------|-----------------|
| **Diagnostic Report** | `DiagnosticReport` | Single lab report/form (e.g., "Complete Blood Count") | Belongs to one user; multiple reports/day allowed; full deletion only |
| **Observation** | `Observation` | Single measured biomarker within a report (e.g., "Hemoglobin: 14.2 g/dL") | Stores original + normalized values; reference ranges per observation |
| **Standard Biomarker** | `StandardBiomarker` | Reference catalog (LOINC/SNOMED codes, names, units) | *Future use:* autocomplete, mapping, normalization rules |
| **User** | `User` | Authenticated patient/user | JWT auth; HTTP-only secure cookies |

---

## 🗃️ Database Schema (PostgreSQL 18.1)

### `diagnostic_reports`
```php
Schema::create('diagnostic_reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('report_type'); // e.g., 'CBC', 'Lipid Panel'
    $table->enum('source', ['manual', 'pdf', 'integration'])->default('manual');
    $table->text('notes')->nullable(); // User comments on report
    $table->timestamps();
});
```

### `observations`
```php
Schema::create('observations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('diagnostic_report_id')
          ->constrained()
          ->cascadeOnDelete();
    
    // Biomarker identity (critical for time-series comparison)
    $table->string('biomarker_name'); // Required display name
    $table->string('biomarker_code')->nullable(); // LOINC/SNOMED code for matching
    
    // Original values (user-entered)
    $table->decimal('original_value', 15, 5);
    $table->string('original_unit');
    
    // Normalized values (system-processed for charts)
    $table->decimal('normalized_value', 15, 5)->nullable();
    $table->string('normalized_unit')->nullable();
    
    // Reference ranges (user-provided per observation)
    $table->decimal('reference_range_min', 15, 5)->nullable();
    $table->decimal('reference_range_max', 15, 5)->nullable();
    $table->string('reference_unit')->nullable(); // May differ from original_unit
    
    $table->timestamps();
});
```

### `standard_biomarkers` *(Foundation for future)*
```php
Schema::create('standard_biomarkers', function (Blueprint $table) {
    $table->id();
    $table->string('code')->unique(); // LOINC/SNOMED code
    $table->string('name');
    $table->string('default_unit')->nullable();
    $table->jsonb('aliases')->default('[]'); // Alternative names/codes
    $table->timestamps();
});
```

---



### Example Payloads
**Create Request**
```json
{
  "report_type": "CBC",
  "notes": "Fasting sample, morning draw",
  "observations": [
    {
      "biomarker_name": "Hemoglobin",
      "biomarker_code": "718-7",
      "original_value": 14.2,
      "original_unit": "g/dL",
      "normalized_value": 14.2,
      "normalized_unit": "g/dL",
      "reference_range_min": 12.0,
      "reference_range_max": 16.0,
      "reference_unit": "g/dL"
    }
  ]
}
```

**Update Request (Partial)**
```json
{
  "observations": [
    {
      "id": 42,
      "biomarker_name": "Hemoglobin",
      "original_value": 13.8,
      "reference_range_max": 15.5
    },
    {
      "biomarker_name": "Hematocrit",
      "biomarker_code": "4544-3",
      "original_value": 42.1,
      "original_unit": "%"
    }
  ]
}
```

---

## Testing Strategy
- **Feature Tests**
    - CRUD operations with auth enforcement
    - Upsert logic: update existing, add new, delete missing observations
    - Policy denial for unauthorized user access
- **Unit Tests**
    - Form request validation rules (edge cases: missing ranges, unit mismatches)
    - Model accessors (`hasReferenceRange`, `isNormalized`)
- **Database Tests**
    - Cascade delete integrity
    - Index usage verification (EXPLAIN ANALYZE)

---

## Deliverables Checklist
- [ ] Database migrations (with indexes)
- [ ] Eloquent models with strict typing & PHPDoc
- [ ] Form Request classes with validation logic
- [ ] API controllers (resourceful)
- [ ] Authorization policies with tests
- [ ] API Resource classes
- [ ] Feature tests (CRUD + upsert logic)
- [ ] Unit tests (validation, accessors)
- [ ] OpenAPI snippet examples in controller PHPDocs
- [ ] `.cursor/rules.md` compliance verification

---

##  Critical Implementation Notes
1. **NUMERIC Precision**: Always use `decimal(15, 5)` — never `float`
2. **Ownership**: Global scope on `DiagnosticReport` to auto-filter by `auth()->id()`
3. **Scopes**: Use module "DiagnosticReport" for code in app folder.
