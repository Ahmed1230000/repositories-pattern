<?php

namespace Packages\AhmedMahmoud\RepositoryPattern\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Class MakeRepository
 * Generates repository pattern structure with customizable options.
 */
class MakeRepository extends GeneratorCommand
{
    protected $signature = 'make:repo {name : The name of the repository (e.g., Product)}
                            {--a|all : Create all files (model, repository, service, controller, requests, migration, resource, collection)}
                            {--m|model : Create model only}
                            {--r|repository : Create repository only}
                            {--s|service : Create service only}
                            {--c|controller : Create controller only}
                            {--f|migration : Create migration only}';

    protected $description = 'Create a new repository pattern structure';

    protected $type = 'Repository';

    protected $filesGenerated = false; // Track if any files were generated
    protected $allFilesGenerated = true; // Track if all required files were generated

    protected function getStub()
    {
        return __DIR__ . '/../../templates/repository.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Repositories';
    }

    public function handle()
    {
        if (!$this->checkRequiredFiles()) {
            return false;
        }

        // Check if no options are provided
        if (
            !$this->option('all') && !$this->option('model') && !$this->option('repository') &&
            !$this->option('service') && !$this->option('controller') && !$this->option('migration')
        ) {
            $this->error('No options specified for make:repo command.');
            $this->info('You must specify at least one of the following options:');
            $this->line('  --all (-a)        Generate all files (model, repository, service, controller, requests, migration, resource, collection)');
            $this->comment('Example: php artisan make:repo Product --all');
            return false;
        }

        $name = $this->argument('name');
        $modelName = Str::studly($name);
        $tableName = Str::snake(Str::plural($name));
        $lowerName = Str::lower($name);

        $this->createDirectories();

        if ($this->option('all')) {
            $this->generateAllFiles($modelName, $tableName, $lowerName);
        } else {
            $this->generateSelectedFiles($modelName, $tableName, $lowerName);
        }

        if ($this->filesGenerated) {
            if ($this->option('all') && $this->allFilesGenerated) {
                $this->info("All repository pattern files for {$modelName} have been successfully generated!");
            } else {
                $this->info("Repository pattern files for {$modelName} created successfully!");
            }
        } else {
            $this->info("No new files were created for {$modelName} as all files and migration already exist.");
        }
    }

    protected function checkRequiredFiles()
    {
        $interfacePath = app_path('Contracts/RepositoryInterface.php');
        $baseRepoPath = app_path('Repositories/BaseRepository.php');

        if (!file_exists($interfacePath) || !file_exists($baseRepoPath)) {
            $this->error('Required files are missing. Please run: php artisan repository:setup');
            return false;
        }
        return true;
    }

    protected function createDirectories()
    {
        $directories = [
            app_path('Models'),
            app_path('Http/Controllers'),
            app_path('Http/Requests'),
            app_path('Http/Resources'),
            app_path('Services'),
            app_path('Providers'),
            database_path('migrations'),
        ];

        if ($this->option('repository') || $this->option('all')) {
            $directories[] = app_path('Repositories');
        }

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                $this->files->makeDirectory($directory, 0755, true);
            }
        }
    }

    protected function generateAllFiles($modelName, $tableName, $lowerName)
    {
        $files = [
            'model' => app_path("Models/{$modelName}.php"),
            'repository' => app_path("Repositories/{$modelName}Repository.php"),
            'controller' => app_path("Http/Controllers/{$modelName}Controller.php"),
            'service' => app_path("Services/{$modelName}Service.php"),
            'store-form-request' => app_path("Http/Requests/{$modelName}StoreFormRequest.php"),
            'update-form-request' => app_path("Http/Requests/{$modelName}UpdateFormRequest.php"),
            'resource' => app_path("Http/Resources/{$modelName}Resource.php"),
            'collection' => app_path("Http/Resources/{$modelName}Collection.php"),
        ];

        $migrationGenerated = true; // Track migration separately
        // Check for existing migration files
        $migrationPath = database_path('migrations');
        $existingMigrations = glob($migrationPath . '/*_create_' . $tableName . '_table.php');
        if (count($existingMigrations) > 0) {
            $this->warn("Migration for {$tableName} table skipped because a migration file already exists: " . basename($existingMigrations[0]));
            $migrationGenerated = false;
        } elseif (Schema::hasTable($tableName)) {
            $this->warn("Migration for {$tableName} table skipped because the table already exists in the database.");
            $migrationGenerated = false;
        } else {
            $files['migration'] = database_path("migrations/" . date('Y_m_d_His') . "_create_{$tableName}_table.php");
        }

        $replacements = [
            '{{ModelName}}' => $modelName,
            '{{modelName}}' => $lowerName,
            '{{tableName}}' => $tableName,
            '{{tableName|studly}}' => Str::studly($tableName),
        ];

        foreach ($files as $stub => $path) {
            if (!$this->generateFile($stub, $path, $replacements)) {
                // If any file (except migration) fails to generate, mark allFilesGenerated as false
                if ($stub !== 'migration') {
                    $this->allFilesGenerated = false;
                }
            } else {
                $this->info("Generated {$stub} file: {$path}");
            }
        }

        // If migration was skipped but all other files were generated, consider it a success
        if (!$migrationGenerated && $this->allFilesGenerated) {
            $this->allFilesGenerated = true;
        }

        $this->registerBinding($modelName);
        $this->addRouteResource($modelName, $tableName);
    }

    protected function generateSelectedFiles($modelName, $tableName, $lowerName)
    {
        $generated = false;
        $replacements = [
            '{{ModelName}}' => $modelName,
            '{{modelName}}' => $lowerName,
            '{{tableName}}' => $tableName,
            '{{tableName|studly}}' => Str::studly($tableName),
        ];

        if ($this->option('model')) {
            if ($this->generateFile('model', app_path("Models/{$modelName}.php"), $replacements)) {
                $generated = true;
                $this->info("Generated model file: " . app_path("Models/{$modelName}.php"));
            }
        }

        if ($this->option('repository')) {
            if ($this->generateFile('repository', app_path("Repositories/{$modelName}Repository.php"), $replacements)) {
                $generated = true;
                $this->info("Generated repository file: " . app_path("Repositories/{$modelName}Repository.php"));
            }
        }

        if ($this->option('service') || $this->option('controller')) {
            if ($this->generateFile('repository', app_path("Repositories/{$modelName}Repository.php"), $replacements)) {
                $generated = true;
                $this->info("Generated repository file: " . app_path("Repositories/{$modelName}Repository.php"));
            }
            if ($this->generateFile('service', app_path("Services/{$modelName}Service.php"), $replacements)) {
                $generated = true;
                $this->info("Generated service file: " . app_path("Services/{$modelName}Service.php"));
            }
        }

        if ($this->option('controller')) {
            if ($this->generateFile('resource', app_path("Http/Resources/{$modelName}Resource.php"), $replacements)) {
                $generated = true;
                $this->info("Generated resource file: " . app_path("Http/Resources/{$modelName}Resource.php"));
            }
            if ($this->generateFile('collection', app_path("Http/Resources/{$modelName}Collection.php"), $replacements)) {
                $generated = true;
                $this->info("Generated collection file: " . app_path("Http/Resources/{$modelName}Collection.php"));
            }
            if ($this->generateFile('controller', app_path("Http/Controllers/{$modelName}Controller.php"), $replacements)) {
                $generated = true;
                $this->info("Generated controller file: " . app_path("Http/Controllers/{$modelName}Controller.php"));
            }
            $this->addRouteResource($modelName, $tableName);
        }

        if ($this->option('migration')) {
            $migrationPath = database_path('migrations');
            $existingMigrations = glob($migrationPath . '/*_create_' . $tableName . '_table.php');
            if (count($existingMigrations) > 0) {
                $this->warn("Migration for {$tableName} table skipped because a migration file already exists: " . basename($existingMigrations[0]));
            } elseif (Schema::hasTable($tableName)) {
                $this->warn("Migration for {$tableName} table skipped because the table already exists in the database.");
            } else {
                if ($this->generateFile('migration', database_path("migrations/" . date('Y_m_d_His') . "_create_{$tableName}_table.php"), $replacements)) {
                    $this->info("Generated migration file for {$tableName} table.");
                    $generated = true;
                }
            }
        }

        if ($generated && ($this->option('repository') || $this->option('service') || $this->option('controller') || $this->option('all'))) {
            $this->registerBinding($modelName);
        }

        $this->filesGenerated = $generated;
    }

    protected function generateFile($stubType, $path, $replacements)
    {
        $stubPath = __DIR__ . "/../../templates/{$stubType}.stub";

        if (!file_exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return false;
        }

        if (file_exists($path)) {
            $this->warn("File {$path} already exists and was not overwritten.");
            return false;
        }

        $content = file_get_contents($stubPath);

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        if (preg_match('/\{\{.*?\}\}/', $content, $matches)) {
            $this->error("Unreplaced placeholders found in {$stubType}: " . implode(', ', $matches));
            return false;
        }

        $directory = dirname($path);
        if (!is_dir($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($path, $content);
        $this->filesGenerated = true;
        return true;
    }

    protected function registerBinding($modelName)
    {
        $providerPath = app_path('Providers/RepositoriesServiceProvider.php');
        $repositoryClass = "App\\Repositories\\{$modelName}Repository";
        $serviceClass = "App\\Services\\{$modelName}Service";

        $bindingCode = <<<PHP
        \$this->app->when(\\{$serviceClass}::class)
            ->needs(\\App\\Contracts\\RepositoryInterface::class)
            ->give(\\{$repositoryClass}::class);
PHP;

        if (!file_exists($providerPath)) {
            $content = <<<PHP
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoriesServiceProvider extends ServiceProvider
{
    public function register()
    {
$bindingCode
    }

    public function boot()
    {
        //
    }
}
PHP;
        } else {
            $content = file_get_contents($providerPath);
            if (!Str::contains($content, $bindingCode)) {
                $content = preg_replace(
                    '/(public\s+function\s+register\s*\(\)\s*\{)([^\}]*?)(\})/s',
                    "$1$2\n$bindingCode\n$3",
                    $content,
                    1
                );
            }
        }

        $this->files->put($providerPath, $content);

        $provider = 'App\\Providers\\RepositoriesServiceProvider::class';
        $filePath = base_path('bootstrap/providers.php');
        if (!is_writable($filePath)) {
            $this->error("Cannot write to {$filePath}. Please manually add {$provider}.");
            return;
        }
        $content = file_get_contents($filePath);
        if (!Str::contains($content, $provider)) {
            $content = str_replace('return [', "return [\n    {$provider},", $content);
            file_put_contents($filePath, $content);
        }
    }

    protected function addRouteResource($modelName, $tableName)
    {
        $routePath = base_path('routes/api.php');
        $controllerClass = "App\\Http\\Controllers\\{$modelName}Controller";
        $routeTableName = $modelName === 'HR' ? 'h_r_s' : $tableName;
        $routeCode = "\nRoute::resource('{$routeTableName}', {$modelName}Controller::class);";
        $useRouteStatement = "use Illuminate\Support\Facades\Route;";
        $useControllerStatement = "use {$controllerClass};";

        if (!is_writable($routePath)) {
            $this->error("Cannot write to {$routePath}. Please manually add the following to routes/api.php:");
            $this->line($useRouteStatement);
            $this->line($useControllerStatement);
            $this->line($routeCode);
            return;
        }

        try {
            $content = file_exists($routePath) ? file_get_contents($routePath) : "<?php\n\n$useRouteStatement;\n";
            $content = preg_replace('/<\?php\s*/', '<?php', $content);
            $content = preg_replace('/\?>\s*$/', '', $content);
            $content = trim($content) . "\n";

            $lines = explode("\n", $content);
            $useStatements = [];
            $routeStatements = [];
            $hasRouteUse = false;
            $hasControllerUse = false;

            foreach ($lines as $line) {
                if (trim($line) === '<?php' || empty(trim($line))) {
                    continue;
                }
                if (preg_match('/^use\s+.*;/', trim($line))) {
                    if (trim($line) === $useRouteStatement) {
                        $hasRouteUse = true;
                    } elseif (trim($line) === $useControllerStatement) {
                        $hasControllerUse = true;
                    } else {
                        $useStatements[] = $line;
                    }
                } elseif (preg_match('/^Route::/', trim($line))) {
                    $routeStatements[] = $line;
                }
            }

            $newContent = ["<?php", ""];
            $newContent[] = $useRouteStatement . ";";
            if (!$hasControllerUse) {
                $newContent[] = $useControllerStatement . ";";
            }
            foreach ($useStatements as $use) {
                $newContent[] = $use;
            }
            $newContent[] = "";
            foreach ($routeStatements as $route) {
                $newContent[] = $route;
            }
            if (!in_array(trim($routeCode), array_map('trim', $routeStatements))) {
                $newContent[] = trim($routeCode);
                $this->info("Added Route::resource for {$routeTableName} to routes/api.php");
            } else {
                $this->warn("Route::resource for {$routeTableName} already exists in routes/api.php");
            }

            $this->files->put($routePath, implode("\n", $newContent) . "\n");
        } catch (\Exception $e) {
            Log::error("Failed to update routes/api.php: {$e->getMessage()}");
            $this->error("Failed to update routes/api.php: {$e->getMessage()}");
            return;
        }
    }
}
// End of MakeRepository.php
// This file is part of the Ahmed Mahmoud Repository Pattern package.
// It is licensed under the MIT License.
// For more information, please refer to the LICENSE file in the root directory of this package.