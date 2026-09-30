---
name: system-architecture
description: >
  Activate this skill whenever creating, modifying, or reviewing code in this project.
  It documents the established architecture, layered patterns, naming conventions,
  and request lifecycle of the aplayan-api Laravel application. Use it to ensure
  every new module, endpoint, model, or test follows the same structure the codebase
  already commits to. Triggers: adding a new resource/module, creating controllers,
  models, DTOs, repositories, form requests, API resources, enums, migrations,
  or writing tests. Also use when onboarding to the codebase or reviewing PRs.
---

# Aplayan API — System Architecture Guide

> **Purpose:** This skill is the single source of truth for how code is structured in
> this project. Follow it whenever you add, modify, or review any layer.

---

## 1. Tech Stack

| Layer            | Technology                       | Version   |
|------------------|----------------------------------|-----------|
| Framework        | Laravel                          | ^13.17    |
| Language         | PHP                              | ^8.4      |
| Authentication   | Laravel Sanctum                  | ^4.0      |
| Testing          | Pest                             | ^5.2      |
| Code Style       | Laravel Pint                     | ^1.27     |
| Database         | SQLite (dev) — any RDBMS in prod |           |

This is an **API-only** application — no Blade views, no Inertia, no frontend assets.

---

## 2. Directory Layout

```
app/
├── DTOs/                  # Data Transfer Objects (readonly classes)
│   └── Contracts/         # DTO interfaces (DataObject)
├── Enums/                 # PHP 8.1+ backed enums
├── Exceptions/            # Custom exception handlers
├── Http/
│   ├── Controllers/       # Thin controllers (delegates to repository)
│   ├── Requests/          # Form Request validation
│   └── Resources/         # Eloquent API Resources
├── Models/                # Eloquent models
├── Providers/             # Service providers (binding interfaces → implementations)
├── Repositories/          # Repository pattern (interface + implementation)
├── Services/              # Service layer (currently empty — reserved for business logic)
└── Traits/                # Reusable traits (e.g. ApiResponse)

bootstrap/
└── app.php                # Exception rendering, middleware, routing config

database/
├── factories/             # Model factories
├── migrations/            # Timestamped migrations
└── seeders/               # Database seeders

routes/
└── api.php                # All API routes (versioned under /api/v1)

tests/
├── Feature/               # Feature tests (Pest)
├── Unit/                  # Unit tests (Pest)
└── Pest.php               # Pest configuration
```

---

## 3. Architectural Pattern — Layered Architecture

The codebase follows a **Controller → DTO → Repository → Model** flow.
The `Services/` directory exists but is currently empty; it is reserved for
complex business logic that doesn't belong in a controller or repository.

### Request Lifecycle (step-by-step)

```
HTTP Request
  │
  ▼
routes/api.php          ── Route dispatches to Controller
  │
  ▼
FormRequest             ── Validates & authorizes (rules via array syntax)
  │
  ▼
Controller              ── Thin orchestrator
  │  ├─ Builds DTO from FormRequest  (DTO::fromStoreRequest / fromUpdateRequest)
  │  └─ Delegates to Repository      (injected via interface)
  │
  ▼
Repository              ── Data access (extends BaseRepository)
  │  └─ Accepts array|DataObject, calls toArray() when DTO
  │
  ▼
Eloquent Model          ── Persists / queries
  │
  ▼
API Resource            ── Transforms model → JSON
  │
  ▼
ApiResponse trait        ── Wraps in { status, message, data } envelope
  │
  ▼
HTTP Response (JSON)
```

---

## 4. Layer-by-Layer Conventions

### 4.1 Models (`app/Models/`)

- **Primary keys:** ULID via `HasUlids` trait — every model uses `$table->ulid('id')->primary()`.
- **Fillable:** Use the `#[Fillable([...])]` PHP attribute (not `$fillable` array).
- **Hidden:** Use the `#[Hidden([...])]` PHP attribute (not `$hidden` array).
- **Casts:** Define via the `casts(): array` method (not `$casts` property).
- **Relationships:** Explicit return type (`BelongsTo`, `HasMany`, etc.).
- **Strict mode:** Enabled in non-production (`Model::shouldBeStrict()`).

```php
#[Fillable(['title', 'description', 'type', 'salary_range', 'location', 'status', 'user_id'])]
class JobApplication extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'type'   => JobType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### 4.2 Enums (`app/Enums/`)

- **Backed string enums** with `TitleCase` case names and `snake_case` or `kebab-case` values.
- Each enum provides a static `values(): array` helper.
- Used in model casts and migration column definitions via `Enum::values()`.

```php
enum JobStatus: string
{
    case Wishlist      = 'wishlist';
    case Applied       = 'applied';
    case OfferDeclined = 'offer_declined';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

### 4.3 DTOs (`app/DTOs/`)

- **Readonly classes** implementing the `DataObject` contract.
- All constructor properties are **nullable** (supports partial updates).
- Named constructors: `fromStoreRequest()`, `fromUpdateRequest()` — accept the corresponding FormRequest.
- `toArray()` uses `array_filter(..., fn ($v) => $v !== null)` to strip null fields.
- Enum properties are cast from their raw value in the named constructor.

```php
readonly class JobApplicationData implements DataObject
{
    public function __construct(
        public ?string    $title       = null,
        public ?JobType   $type        = null,
        public ?JobStatus $status      = null,
        // ... other fields
    ) {}

    public static function fromStoreRequest(StoreJobApplicationRequest $request): self { /* ... */ }
    public static function fromUpdateRequest(UpdateJobApplicationRequest $request): self { /* ... */ }

    public function toArray(): array
    {
        return array_filter([
            'title'  => $this->title,
            'type'   => $this->type?->value,
            'status' => $this->status?->value,
        ], fn ($value) => $value !== null);
    }
}
```

### 4.4 Repositories (`app/Repositories/`)

- **Interface-driven**: every repository has a matching `*RepositoryInterface`.
- Concrete repositories extend `BaseRepository` and implement their domain interface.
- `BaseRepository` provides: `all()`, `find()`, `findOrFail()`, `paginate()`, `create()`, `update()`, `delete()`.
- `create()` and `update()` accept `array|DataObject` — the base class resolves it transparently.
- Bindings are registered in `AppServiceProvider::register()`.

```php
// Interface
interface JobApplicationRepositoryInterface extends BaseRepositoryInterface {}

// Implementation
class JobApplicationRepository extends BaseRepository implements JobApplicationRepositoryInterface
{
    public function __construct(JobApplication $model)
    {
        parent::__construct($model);
    }
}

// Binding (AppServiceProvider)
$this->app->bind(JobApplicationRepositoryInterface::class, JobApplicationRepository::class);
```

### 4.5 Controllers (`app/Http/Controllers/`)

- Extend the abstract `Controller` base class.
- **Thin** — no business logic. Delegates to the repository via constructor-injected interface.
- Uses the `ApiResponse` trait for consistent JSON envelopes.
- Returns `JobApplicationResource` (or collection) wrapped in `successResponse()`.
- Store/Update actions build a DTO from the FormRequest before passing to repository.

```php
class JobApplicationController extends Controller
{
    use ApiResponse;

    public function __construct(private JobApplicationRepositoryInterface $repository) {}

    public function store(StoreJobApplicationRequest $request)
    {
        $dto = JobApplicationData::fromStoreRequest($request);
        $jobApplication = $this->repository->create($dto);

        return $this->successResponse(
            new JobApplicationResource($jobApplication),
            'Job application created successfully',
            201
        );
    }
}
```

### 4.6 Form Requests (`app/Http/Requests/`)

- One request class per action: `Store{Model}Request`, `Update{Model}Request`.
- Validation rules use **array syntax** (not pipe-separated strings).
- Enum validation uses `Rule::enum(EnumClass::class)`.
- Store requests use `'required'`; Update requests use `'sometimes'`.

### 4.7 API Resources (`app/Http/Resources/`)

- One resource per model: `{Model}Resource extends JsonResource`.
- Currently delegates to `parent::toArray()` — customize when field selection is needed.

### 4.8 API Response Envelope (`app/Traits/ApiResponse`)

All JSON responses follow this structure:

```json
// Success
{ "status": "success", "message": "...", "data": { ... } }

// Error
{ "status": "failed", "message": "...", "errors": [...] }
```

- `successResponse(mixed $data, string $message, int $code)` — wraps data.
- `errorResponse(string $message, int $code, ?array $errors)` — wraps errors.
- The trait is used in controllers and in `ApiExceptionHandler`.

### 4.9 Exception Handling (`app/Exceptions/ApiExceptionHandler`)

- All API requests (`api/*`) are routed through `ApiExceptionHandler::handle()` via `bootstrap/app.php`.
- Maps framework exceptions to consistent JSON error responses:
  - `ValidationException` → 422
  - `ModelNotFoundException` → 404
  - `AuthorizationException` → 403
  - `AuthenticationException` → 401
  - `HttpException` → dynamic status code
  - `QueryException` → 500
  - Fallback → 500

### 4.10 Routing (`routes/api.php`)

- API-only (no `web.php` routes).
- All routes are versioned under `/api/v1`.
- `Route::apiResource()` for CRUD resources.
- Public vs protected routes are separated by `auth:sanctum` middleware group.
- Stateful API middleware is enabled (`$middleware->statefulApi()`).

### 4.11 Migrations (`database/migrations/`)

- Primary keys: `$table->ulid('id')->primary()`.
- Foreign keys: `$table->foreignUlid('...')` with `->constrained()->cascadeOnDelete()`.
- Enum columns: `$table->enum('column', Enum::values())` — uses the enum's `values()` helper.
- Always include `$table->timestamps()`.

### 4.12 Authentication

- **Laravel Sanctum** with stateful API sessions.
- `AuthController` handles login, register, logout, password reset (currently stubbed).
- Auth-specific form requests: `LoginRequest`, `RegisterRequest`.

---

## 5. Naming Conventions

| Artifact                | Convention                                 | Example                              |
|-------------------------|--------------------------------------------|--------------------------------------|
| Model                   | `PascalCase` singular                      | `JobApplication`                     |
| Controller              | `{Model}Controller`                        | `JobApplicationController`           |
| Form Request (store)    | `Store{Model}Request`                      | `StoreJobApplicationRequest`         |
| Form Request (update)   | `Update{Model}Request`                     | `UpdateJobApplicationRequest`        |
| API Resource            | `{Model}Resource`                          | `JobApplicationResource`             |
| DTO                     | `{Model}Data`                              | `JobApplicationData`                 |
| Repository Interface    | `{Model}RepositoryInterface`               | `JobApplicationRepositoryInterface`  |
| Repository              | `{Model}Repository`                        | `JobApplicationRepository`           |
| Enum                    | `PascalCase` descriptive                   | `JobStatus`, `JobType`               |
| Migration               | `create_{table}_table`                     | `create_job_applications_table`      |
| Route resource          | kebab-case plural                          | `job-applications`                   |
| Database table          | snake_case plural                          | `job_applications`                   |
| Database column         | snake_case                                 | `salary_range`, `user_id`            |

---

## 6. Checklist — Adding a New Resource/Module

When adding a new resource (e.g. `Company`), create files in this order:

1. **Enum(s)** — `app/Enums/CompanyType.php` (if applicable)
2. **Migration** — `php artisan make:migration create_companies_table`
3. **Model** — `app/Models/Company.php` (with `#[Fillable]`, `HasUlids`, `casts()`, relationships)
4. **Factory** — `database/factories/CompanyFactory.php`
5. **DTO Contract** — Already exists at `app/DTOs/Contracts/DataObject.php` (reuse it)
6. **DTO** — `app/DTOs/CompanyData.php` (readonly, implements `DataObject`)
7. **Repository Interface** — `app/Repositories/CompanyRepositoryInterface.php` (extends `BaseRepositoryInterface`)
8. **Repository** — `app/Repositories/CompanyRepository.php` (extends `BaseRepository`)
9. **Bind in `AppServiceProvider`** — `$this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class)`
10. **Form Requests** — `app/Http/Requests/StoreCompanyRequest.php`, `UpdateCompanyRequest.php`
11. **API Resource** — `app/Http/Resources/CompanyResource.php`
12. **Controller** — `app/Http/Controllers/CompanyController.php` (inject repository interface, use `ApiResponse`)
13. **Route** — Add `Route::apiResource('companies', CompanyController::class)` to `routes/api.php`
14. **Tests** — Feature test under `tests/Feature/` using Pest

---

## 7. Anti-Patterns (Do NOT Do)

- ❌ **Inline validation in controllers** — Always use Form Requests.
- ❌ **Direct Eloquent in controllers** — Always go through the Repository layer.
- ❌ **`$fillable` / `$hidden` arrays** — Use PHP attributes `#[Fillable]` / `#[Hidden]`.
- ❌ **`$casts` property** — Use the `casts(): array` method.
- ❌ **Auto-increment integer IDs** — Always use ULIDs.
- ❌ **Raw JSON responses** — Always use `ApiResponse` trait with `successResponse()` / `errorResponse()`.
- ❌ **Untyped method signatures** — Always add return types and parameter types.
- ❌ **Lazy loading** — Always eager-load relationships; strict mode is enforced.
- ❌ **Pipe-separated validation rules** — Use array syntax.
- ❌ **Skipping the DTO for create/update** — Always build a DTO from the FormRequest.

---

## 8. Testing Conventions

- **Framework:** Pest ^5.2 with `pestphp/pest-plugin-laravel`.
- **Location:** Feature tests in `tests/Feature/`, unit tests in `tests/Unit/`.
- **Base class:** `TestCase` is auto-bound in `tests/Pest.php`.
- **RefreshDatabase:** Commented out in `Pest.php` — enable per-test or globally as needed.
- **Create tests with:** `php artisan make:test --pest {TestName}`.

---

## 9. Service Provider Bindings

All repository interface → implementation bindings live in `AppServiceProvider::register()`.
When adding a new repository pair, add the binding there:

```php
public function register(): void
{
    $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
    // ... other bindings
}
```

---

## 10. Bootstrap & Middleware (`bootstrap/app.php`)

- **Routing:** API-only (`api.php` + `console.php`), health check at `/up`.
- **Middleware:** `statefulApi()` enabled for Sanctum session support.
- **Exceptions:** All requests render as JSON. API routes (`api/*`) delegate to `ApiExceptionHandler`.
