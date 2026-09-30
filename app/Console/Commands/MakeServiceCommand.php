<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Signature('make:service {name : The name of the service (e.g. AuthService or Auth)} {--repository= : Optional repository interface to inject}')]
#[Description('Create a new service class boilerplate')]
class MakeServiceCommand extends Command
{
    public function handle(): int
    {
        $rawName = trim($this->argument('name'));
        $rawName = str_replace(['/', '\\'], '', $rawName);

        $baseName = Str::studly(preg_replace('/Service$/i', '', $rawName));
        $className = "{$baseName}Service";

        $directory = app_path('Services');
        File::ensureDirectoryExists($directory);

        $servicePath = "{$directory}/{$className}.php";

        if (File::exists($servicePath)) {
            $this->components->warn("Service [{$servicePath}] already exists.");

            return self::FAILURE;
        }

        $repositoryOption = $this->option('repository');
        $repositoryInterface = null;

        if ($repositoryOption) {
            $repoBase = Str::studly(preg_replace('/Repository(Interface)?$/i', '', trim($repositoryOption)));
            $repositoryInterface = "{$repoBase}RepositoryInterface";
        }

        File::put($servicePath, $this->buildServiceStub($className, $repositoryInterface));
        $this->components->info("Service [app/Services/{$className}.php] created successfully.");

        return self::SUCCESS;
    }

    private function buildServiceStub(string $className, ?string $repositoryInterface): string
    {
        if ($repositoryInterface) {
            return <<<PHP
<?php

namespace App\Services;

use App\Repositories\\{$repositoryInterface};

class {$className}
{
    public function __construct(
        private readonly {$repositoryInterface} \$repository
    ) {}
}

PHP;
        }

        return <<<PHP
<?php

namespace App\Services;

class {$className}
{
    //
}

PHP;
    }
}
