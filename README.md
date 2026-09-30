# Laravel API Boilerplate

A production-ready, highly opinionated REST API boilerplate built on **Laravel 13** and **PHP 8.4**. Engineered for scalable decoupled architectures (e.g. React, Vue, Next.js, mobile apps) with first-class support for **dual authentication powered by Laravel Sanctum & Laravel Fortify (Stateful SPA Session & Stateless Bearer Token)**, **Social Login**, **Two-Factor Authentication (2FA)**, and **Passkeys (WebAuthn)**.

---

## 🚀 Key Features

* **Dual Authentication Engine (Sanctum + Fortify):** Combines **Laravel Sanctum** (SPA cookie session & Bearer token management) with **Laravel Fortify** (headless authentication backend, password resets, and 2FA engine). The API dynamically resolves between session cookies and Sanctum Bearer tokens without code duplication.
* **Passkeys (WebAuthn / FIDO2):** First-party passwordless biometric login and security key management powered by `laravel/passkeys` and `@laravel/passkeys`.
* **Two-Factor Authentication (TOTP):** Full 2FA workflow via `laravel/fortify` featuring QR code SVG generation, encrypted secrets, confirmation, and recovery codes.
* **Social Authentication (OAuth 2.0):** Google and Facebook login flows via `laravel/socialite` supporting both SPA API calls and browser redirect flows.
* **Layered Clean Architecture:** Strict separation of concerns following `Controller -> DTO -> Repository -> Model / Service`.
* **ULID Primary Keys:** Collision-resistant, URL-safe, lexicographically sortable ULIDs on all database tables.
* **Custom Artisan Generators:** Scaffold clean architecture components with one command:
  * `php artisan make:dto`
  * `php artisan make:enum`
  * `php artisan make:repository`
  * `php artisan make:service`
* **Automated API Documentation:** Interactive OpenAPI/Swagger documentation generated on the fly via [Dedoc Scramble](https://scramble.dedoc.co) at `/docs/api`.
* **Strict Quality Defaults:** Eloquent strict mode enabled, strict typing, comprehensive Pest testing setup, and Laravel Pint code formatting.

---

## 🛠 Tech Stack

| Component | Technology | Version |
|---|---|---|
| **Framework** | Laravel | ^13.17 |
| **Language** | PHP | ^8.4 |
| **Authentication** | Laravel Sanctum & Fortify | ^4.0 / ^1.40 |
| **Passkeys** | Laravel Passkeys (WebAuthn) | ^0.2.1 |
| **Social Login** | Laravel Socialite | ^5.31 |
| **Testing** | Pest PHP | ^5.2 |
| **Code Formatter** | Laravel Pint | ^1.27 |
| **API Docs** | Dedoc Scramble | ^0.12 |

---

## 📂 Architecture & Directory Layout

```
app/
├── Console/Commands/      # Custom artisan generators (DTO, Enum, Repository, Service)
├── DTOs/                  # Readonly Data Transfer Objects implementing DataObject
├── Enums/                 # PHP 8 backed string/int enums with values() helpers
├── Exceptions/            # Centralized API exception handling (ApiExceptionHandler)
├── Http/
│   ├── Controllers/       # Thin orchestrators returning ApiResponse envelopes
│   ├── Requests/          # Validated FormRequests (array syntax)
│   └── Resources/         # Eloquent API Resources for consistent JSON transformations
├── Models/                # Eloquent models (HasUlids, #[Fillable], #[Hidden], casts())
├── Providers/             # Service bindings (Interfaces -> Repositories/Services)
├── Repositories/          # Repository pattern (Interfaces + BaseRepository implementations)
├── Services/              # Core business logic services (AuthService, Token, Session)
└── Traits/                # Reusable traits (ApiResponse)
```

All responses conform to a unified JSON envelope:
```json
// Success
{ "status": "success", "message": "Operation successful", "data": { ... } }

// Error
{ "status": "failed", "message": "Validation error", "errors": { ... } }
```

### 🔐 Dual Auth: How Sanctum & Fortify Work Together

The authentication architecture pairs **Laravel Sanctum** with **Laravel Fortify** in headless API mode:

1. **Laravel Sanctum (`Sanctum`):**
   * Inspects incoming requests via `EnsureFrontendRequestsAreStateful::fromFrontend($request)`.
   * For SPAs matching configured `SANCTUM_STATEFUL_DOMAINS`, it utilizes cookie-based session authentication with CSRF protection (`/sanctum/csrf-cookie`).
   * For mobile apps and external API clients, it issues and validates cryptographic Bearer tokens (`personal_access_tokens`).
2. **Laravel Fortify (`Fortify`):**
   * Acts as the headless authentication engine.
   * Powers the **Two-Factor Authentication (TOTP)** pipeline (secret generation, QR code SVGs, confirmation, and recovery codes).
   * Powers the password reset and email verification brokers.
3. **Unified Resolution (`AuthService`):**
   * Dynamic binding in `AppServiceProvider` routes requests to `SessionAuthService` or `TokenAuthService`.
   * Controller actions call `$this->service->authenticated($user)`, which seamlessly returns `{ "user": { ... } }` (setting session cookies) or `{ "user": { ... }, "token": "..." }` without code duplication.

---

## ⚡ Getting Started

### 1. Requirements
* PHP 8.4+ (with `ext-pdo`, `ext-mbstring`, `ext-openssl`, `ext-sodium`, `ext-gmp`)
* Composer 2+
* SQLite, MySQL 8+, or PostgreSQL 14+

### 2. Installation

Clone the repository and install dependencies:
```bash
git clone https://github.com/codebykenth/laravel-api-boilerplate.git
cd laravel-api-boilerplate
composer install
```

Configure your environment:
```bash
cp .env.example .env
php artisan key:generate
```

Run database migrations:
```bash
php artisan migrate
```

Start the local development server:
```bash
php artisan serve
```

Your API will be running at `http://localhost:8000`.  
Explore interactive API documentation at: `http://localhost:8000/docs/api`

---

## ⚙️ Environment Configuration

Edit your `.env` file to customize authentication and integrations:

```env
APP_NAME="API Boilerplate"
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173

# Sanctum Stateful Domains (for SPA cookies)
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:3000
SESSION_DOMAIN=localhost

# Google OAuth Credentials
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT=http://localhost:5173/auth/callback/google

# Passkeys (WebAuthn)
PASSKEYS_USER_HANDLE_SECRET=your-secure-passkey-secret
```

---

## 📋 API Endpoints Summary

All routes are versioned under `/api/v1`.

### Authentication
* `POST /api/v1/auth/register` — Register a new account
* `POST /api/v1/auth/login` — Login (supports standard credentials & 2FA challenge responses)
* `POST /api/v1/auth/two-factor-challenge` — Verify 6-digit TOTP or recovery code
* `POST /api/v1/auth/logout` — Logout (revokes token or invalidates session)
* `POST /api/v1/auth/forgot-password` — Send password reset link
* `POST /api/v1/auth/reset-password` — Reset password using token

### Social Authentication
* `GET /api/v1/auth/{provider}` — Get redirect URL for OAuth provider (`google`, `facebook`)
* `GET /api/v1/auth/{provider}/callback` — Handle provider OAuth callback

### Passkeys (WebAuthn)
* `GET /api/v1/auth/passkeys/options` — Get challenge options for passwordless login
* `POST /api/v1/auth/passkeys/login` — Verify signed assertion and authenticate user
* `GET /api/v1/passkeys/options` — Get challenge options for passkey registration *(Protected)*
* `POST /api/v1/passkeys` — Register and store new passkey credential *(Protected)*
* `GET /api/v1/passkeys` — List user's registered passkeys *(Protected)*
* `DELETE /api/v1/passkeys/{passkey}` — Revoke a passkey *(Protected)*

### Two-Factor Authentication Management *(Protected)*
* `POST /api/v1/auth/two-factor/enable` — Enable 2FA, generate secret key and QR code SVG
* `POST /api/v1/auth/two-factor/confirm` — Confirm and activate 2FA with 6-digit TOTP code
* `DELETE /api/v1/auth/two-factor/disable` — Deactivate 2FA
* `GET /api/v1/auth/two-factor/recovery-codes` — View emergency recovery codes
* `POST /api/v1/auth/two-factor/recovery-codes` — Regenerate emergency recovery codes

---

## 📖 In-Depth Guides

* [2FA and Passkeys Integration Guide (with React Frontend Examples)](docs/auth_2fa_and_passkeys_guide.md)
* [Custom Artisan Commands Guide (`make:dto`, `make:enum`, `make:repository`, `make:service`)](docs/custom_commands_guide.md)

---

## 🧪 Testing & Code Style

Run the automated test suite using [Pest](https://pestphp.com):
```bash
php artisan test
```

Format code following project standards using [Laravel Pint](https://laravel.com/docs/pint):
```bash
vendor/bin/pint
```

---

## 📄 License

This boilerplate is open-sourced software licensed under the [MIT license](LICENSE).
