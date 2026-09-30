<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Signature('make:repository {name : The name of the repository (e.g. UserRepository or User)} {--model= : The model class name}')]
#[Description('Create a new repository class and interface boilerplate')]
class MakeRepositoryCommand extends Command
{
    public function handle(): int
    {
        $rawName = trim($this->argument('name'));
        $rawName = str_replace(['/', '\\'], '', $rawName);

        // Normalize base name (e.g. "Repositories/UserRepository", "UserRepository", or "User" -> "User")
        $baseName = Str::studly(preg_replace('/Repository$/i', '', $rawName));
        $className = "{$baseName}Repository";
        $interfaceName = "{$baseName}RepositoryInterface";

        $modelName = $this->option('model')
            ? Str::studly($this->option('model'))
            : $baseName;

        $directory = app_path('Repositories');
        File::ensureDirectoryExists($directory);

        $interfacePath = "{$directory}/{$interfaceName}.php";
        $repositoryPath = "{$directory}/{$className}.php";

        // Generate Interface
        if (File::exists($interfacePath)) {
            $this->components->warn("Interface [{$interfacePath}] already exists.");
        } else {
            File::put($interfacePath, $this->buildInterfaceStub($interfaceName));
            $this->components->info("Interface [app/Repositories/{$interfaceName}.php] created successfully.");
        }

        // Generate Repository Class
        if (File::exists($repositoryPath)) {
            $this->components->warn("Repository [{$repositoryPath}] already exists.");
        } else {
            File::put($repositoryPath, $this->buildRepositoryStub($className, $interfaceName, $modelName));
            $this->components->info("Repository [app/Repositories/{$className}.php] created successfully.");
        }

        $this->newLine();
        $this->components->twoColumnDetail(
            'Binding reminder',
            "AppServiceProvider: bind({$interfaceName}::class, {$className}::class)"
        );

        return self::SUCCESS;
    }

    private function buildInterfaceStub(string $interfaceName): string
    {
        return <<<PHP
<?php

namespace App\Repositories;

interface {$interfaceName} extends BaseRepositoryInterface {}

PHP;
    }

    private function buildRepositoryStub(string $className, string $interfaceName, string $modelName): string
    {
        return <<<PHP
<?php

namespace App\Repositories;

use App\Models\\{$modelName};

class {$className} extends BaseRepository implements {$interfaceName}
{
    public function __construct({$modelName} \$model)
    {
        parent::__construct(\$model);
    }
}

PHP;
    }
}
