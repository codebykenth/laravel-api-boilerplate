<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Signature('make:dto {name : The name of the DTO (e.g. LoginData or Login)} {--request= : Optional FormRequest class name}')]
#[Description('Create a new Data Transfer Object (DTO) class')]
class MakeDtoCommand extends Command
{
    public function handle(): int
    {
        $rawName = trim($this->argument('name'));
        $rawName = str_replace(['/', '\\'], '', $rawName);

        $baseName = Str::studly(preg_replace('/(Data|Dto)$/i', '', $rawName));
        $className = "{$baseName}Data";

        $directory = app_path('DTOs');
        File::ensureDirectoryExists($directory);

        $dtoPath = "{$directory}/{$className}.php";

        if (File::exists($dtoPath)) {
            $this->components->warn("DTO [{$dtoPath}] already exists.");

            return self::FAILURE;
        }

        $requestOption = $this->option('request');
        $requestClass = null;

        if ($requestOption) {
            $reqBase = Str::studly(trim($requestOption));
            $requestClass = Str::endsWith($reqBase, 'Request') ? $reqBase : "{$reqBase}Request";
        }

        File::put($dtoPath, $this->buildDtoStub($className, $requestClass));
        $this->components->info("DTO [app/DTOs/{$className}.php] created successfully.");

        return self::SUCCESS;
    }

    private function buildDtoStub(string $className, ?string $requestClass): string
    {
        if ($requestClass) {
            return <<<PHP
<?php

namespace App\DTOs;

use App\DTOs\Contracts\DataObject;
use App\Http\Requests\\{$requestClass};

readonly class {$className} implements DataObject
{
    public function __construct(
        //
    ) {}

    public static function fromRequest({$requestClass} \$request): self
    {
        return new self(
            //
        );
    }

    public function toArray(): array
    {
        return array_filter([
            //
        ], fn (\$value) => \$value !== null);
    }
}

PHP;
        }

        return <<<PHP
<?php

namespace App\DTOs;

use App\DTOs\Contracts\DataObject;
use Illuminate\Http\Request;

readonly class {$className} implements DataObject
{
    public function __construct(
        //
    ) {}

    public static function fromRequest(Request \$request): self
    {
        return new self(
            //
        );
    }

    public function toArray(): array
    {
        return array_filter([
            //
        ], fn (\$value) => \$value !== null);
    }
}

PHP;
    }
}
