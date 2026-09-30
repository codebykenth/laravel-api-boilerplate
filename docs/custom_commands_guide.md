# Custom Artisan Commands Guide

This project includes custom Artisan generators to enforce consistency with the layered architecture (`Controller -> DTO -> Repository -> Model / Service`).

---

## Commands Overview

| Command | Purpose | Target Directory |
|---|---|---|
| `php artisan make:dto` | Generates a readonly Data Transfer Object implementing `DataObject` | `app/DTOs/` |
| `php artisan make:enum` | Generates a backed string/int or pure enum with `values()` helper | `app/Enums/` |
| `php artisan make:repository` | Generates both repository interface and class extending `BaseRepository` | `app/Repositories/` |
| `php artisan make:service` | Generates a business logic service with optional repository injection | `app/Services/` |

---

## 1. `make:dto`

Creates a `readonly` DTO class that implements `App\DTOs\Contracts\DataObject`.

### Signature
```bash
php artisan make:dto {name} {--request=}
```

### Arguments & Options
* `{name}` *(required)*: The DTO class name. Suffixes like `Dto` or `Data` are normalized automatically (e.g. `Login`, `LoginDto`, and `LoginData` all create `LoginData.php`).
* `--request=` *(optional)*: Links the DTO to a FormRequest, generating a `fromRequest()` named constructor.

### Examples

#### Basic DTO
```bash
php artisan make:dto UserProfile
# Generates: app/DTOs/UserProfileData.php
```

#### DTO with FormRequest Integration
```bash
php artisan make:dto Register --request=RegisterRequest
# Generates: app/DTOs/RegisterData.php with fromRequest(RegisterRequest $request)
```

Generated code stub:
```php
namespace App\DTOs;

use App\DTOs\Contracts\DataObject;
use App\Http\Requests\RegisterRequest;

readonly class RegisterData implements DataObject
{
    public function __construct(
        // public ?string $property = null,
    ) {}

    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            // property: $request->validated('property'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            // 'property' => $this->property,
        ], fn ($value) => $value !== null);
    }
}
```

---

## 2. `make:enum`

Creates a PHP 8 enum equipped with static helper methods for validation rules and migration column definitions.

### Signature
```bash
php artisan make:enum {name} {--int} {--pure}
```

### Arguments & Options
* `{name}` *(required)*: The Enum name in StudlyCase (e.g., `PaymentStatus`).
* `--int` *(optional)*: Generates an integer-backed enum (`enum Name: int`).
* `--pure` *(optional)*: Generates a non-backed pure enum with `names(): array`.
* Default (no flags): Generates a string-backed enum (`enum Name: string`) with `values(): array`.

### Examples

#### String Backed Enum (Default)
```bash
php artisan make:enum JobStatus
# Generates: app/Enums/JobStatus.php (enum JobStatus: string)
```

Generated code stub:
```php
namespace App\Enums;

enum JobStatus: string
{
    // case Example = 'example';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

#### Integer Backed Enum
```bash
php artisan make:enum Priority --int
# Generates: app/Enums/Priority.php (enum Priority: int)
```

---

## 3. `make:repository`

Generates both the repository interface and concrete implementation according to the repository pattern.

### Signature
```bash
php artisan make:repository {name} {--model=}
```

### Arguments & Options
* `{name}` *(required)*: The repository name. Suffixes like `Repository` are handled automatically (e.g., `Company` or `CompanyRepository`).
* `--model=` *(optional)*: The Eloquent model class to bind. If omitted, uses the base name.

### Examples
```bash
php artisan make:repository Company --model=Company
```

This creates two files:
1. `app/Repositories/CompanyRepositoryInterface.php`:
   ```php
   namespace App\Repositories;

   interface CompanyRepositoryInterface extends BaseRepositoryInterface {}
   ```
2. `app/Repositories/CompanyRepository.php`:
   ```php
   namespace App\Repositories;

   use App\Models\Company;

   class CompanyRepository extends BaseRepository implements CompanyRepositoryInterface
   {
       public function __construct(Company $model)
       {
           parent::__construct($model);
       }
   }
   ```

### Binding in Service Provider
After running `make:repository`, bind the interface in `app/Providers/AppServiceProvider.php`:
```php
$this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
```

---

## 4. `make:service`

Creates a service class in `app/Services/` for complex business logic, with optional dependency injection of a repository interface.

### Signature
```bash
php artisan make:service {name} {--repository=}
```

### Arguments & Options
* `{name}` *(required)*: The service name. Suffixes like `Service` are handled automatically (e.g. `Payment` or `PaymentService`).
* `--repository=` *(optional)*: The repository interface to inject in the constructor.

### Examples

#### Basic Service
```bash
php artisan make:service Invoice
# Generates: app/Services/InvoiceService.php
```

#### Service with Repository Injection
```bash
php artisan make:service Billing --repository=UserRepository
# Generates: app/Services/BillingService.php injecting UserRepositoryInterface
```

Generated code stub:
```php
namespace App\Services;

use App\Repositories\UserRepositoryInterface;

class BillingService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}
}
```
