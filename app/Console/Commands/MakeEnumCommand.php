<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Signature('make:enum {name : The name of the enum} {--int : Generate an integer backed enum} {--pure : Generate a non-backed pure enum}')]
#[Description('Create a new enum with a values() helper method')]
class MakeEnumCommand extends Command
{
    public function handle(): int
    {
        $rawName = trim($this->argument('name'));
        $rawName = str_replace(['/', '\\'], '', $rawName);

        $className = Str::studly($rawName);

        $directory = app_path('Enums');
        File::ensureDirectoryExists($directory);

        $enumPath = "{$directory}/{$className}.php";

        if (File::exists($enumPath)) {
            $this->components->warn("Enum [{$enumPath}] already exists.");

            return self::FAILURE;
        }

        $type = 'string';
        if ($this->option('int')) {
            $type = 'int';
        } elseif ($this->option('pure')) {
            $type = null;
        }

        File::put($enumPath, $this->buildEnumStub($className, $type));
        $this->components->info("Enum [app/Enums/{$className}.php] created successfully.");

        return self::SUCCESS;
    }

    private function buildEnumStub(string $className, ?string $type): string
    {
        if ($type === null) {
            return <<<PHP
<?php

namespace App\Enums;

enum {$className}
{
    // case Example;

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }
}

PHP;
        }

        return <<<PHP
<?php

namespace App\Enums;

enum {$className}: {$type}
{
    // case Example = {$this->exampleValue($type)};

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

PHP;
    }

    private function exampleValue(string $type): string
    {
        return $type === 'int' ? '1' : "'example'";
    }
}
