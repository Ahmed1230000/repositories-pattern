<?php

namespace Packages\AhmedMahmoud\RepositoryPattern\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Class MakeRepository
 *
 * Artisan command to generate Repository Pattern files
 * with customizable options for model, repository, service,
 * controller, requests, migration, resource, and collection.
 *
 * @package Packages\AhmedMahmoud\RepositoryPattern\Commands
 */
class MakeRepository extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repo {name : The name of the repository (e.g., Product)}
                            {--a|all : Create all files (model, repository, service, controller, requests, migration, resource, collection)}
                            {--m|model : Create model only}
                            {--r|repository : Create repository only}
                            {--s|service : Create service only}
                            {--c|controller : Create controller only}
                            {--f|migration : Create migration only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository pattern structure';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Repository';

    /**
     * Flag indicating if any files were generated in current run.
     *
     * @var bool
     */
    protected $filesGenerated = false;

    /**
     * Flag indicating if all required files were generated.
     *
     * @var bool
     */
    protected $allFilesGenerated = true;

    /**
     * Get the stub file for the generator command.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../../templates/repository.stub';
    }

    /**
     * Get the default namespace for the generated repository.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Repositories';
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        if (!$this->checkRequiredFiles()) {
            return false;
        }

        if (!$this->anyOptionSelected()) {
            $this->displayNoOptionsError();
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

        $this->displayGenerationSummary($modelName);

        return null;
    }

    /**
     * Check if required base files exist.
     *
     * @return bool
     */
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

    /**
     * Check if at least one generation option is selected.
     *
     * @return bool
     */
    protected function anyOptionSelected()
    {
        return $this->option('all') || $this->option('model') || $this->option('repository') ||
            $this->option('service') || $this->option('controller') || $this->option('migration');
    }

    /**
     * Display error message when no option is selected.
     *
     * @return void
     */
    protected function displayNoOptionsError()
    {
        $this->error('No options specified for make:repo command.');
        $this->info('You must specify at least one of the following options:');
        $this->line('  --all (-a)        Generate all files (model, repository, service, controller, requests, migration, resource, collection)');
        $this->comment('Example: php artisan make:repo Product --all');
    }

    /**
     * Create necessary directories for files.
     *
     * @return void
     */
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

    /**
     * Generate all repository pattern files.
     *
     * @param string $modelName
     * @param string $tableName
     * @param string $lowerName
     * @return void
     */
    protected function generateAllFiles(string $modelName, string $tableName, string $lowerName)
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

        // Determine if migration should be generated
        $migrationGenerated = true;
        $migrationPath = database_path('migrations');
        $existingMigrations = glob($migrationPath . "/*_create_{$tableName}_table.php");

        if (count($existingMigrations) > 0) {
            $this->warn("Migration for {$tableName} table skipped because migration file already exists: " . basename($existingMigrations[0]));
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
            if (!$this->generateFile($stub, $path, $replacements) && $stub !== 'migration') {
                $this->allFilesGenerated = false;
            } else {
                $this->info("Generated {$stub} file: {$path}");
            }
        }

        // Register bindings and add routes if all or some files were generated
        if ($migrationGenerated && $this->allFilesGenerated) {
            $this->registerBinding($modelName);
            $this->addRouteResource($modelName, $tableName);
        }
    }

    /**
     * Generate files based on selected options.
     *
     * @param string $modelName
     * @param string $tableName
     * @param string $lowerName
     * @return void
     */
    protected function generateSelectedFiles(string $modelName, string $tableName, string $lowerName)
    {
        $generated = false;
        $replacements = [
            '{{ModelName}}' => $modelName,
            '{{modelName}}' => $lowerName,
            '{{tableName}}' => $tableName,
            '{{tableName|studly}}' => Str::studly($tableName),
        ];

        // Model generation
        if ($this->option('model')) {
            $generated |= $this->generateAndLog('model', app_path("Models/{$modelName}.php"), $replacements);
        }

        // Repository generation
        if ($this->option('repository')) {
            $generated |= $this->generateAndLog('repository', app_path("Repositories/{$modelName}Repository.php"), $replacements);
        }

        // Service & Repository for service or controller options
        if ($this->option('service') || $this->option('controller')) {
            $generated |= $this->generateAndLog('repository', app_path("Repositories/{$modelName}Repository.php"), $replacements);
            $generated |= $this->generateAndLog('service', app_path("Services/{$modelName}Service.php"), $replacements);
        }

        // Controller, Resource & Collection generation
        if ($this->option('controller')) {
            $generated |= $this->generateAndLog('resource', app_path("Http/Resources/{$modelName}Resource.php"), $replacements);
            $generated |= $this->generateAndLog('collection', app_path("Http/Resources/{$modelName}Collection.php"), $replacements);
            $generated |= $this->generateAndLog('controller', app_path("Http/Controllers/{$modelName}Controller.php"), $replacements);
            $this->addRouteResource($modelName, $tableName);
        }

        // Migration generation
        if ($this->option('migration')) {
            $migrationPath = database_path('migrations');
            $existingMigrations = glob($migrationPath . "/*_create_{$tableName}_table.php");
            if (count($existingMigrations) > 0) {
                $this->warn("Migration for {$tableName} table skipped because migration file already exists: " . basename($existingMigrations[0]));
            } elseif (Schema::hasTable($tableName)) {
                $this->warn("Migration for {$tableName} table skipped because the table already exists in the database.");
            } else {
                $migrationFile = database_path("migrations/" . date('Y_m_d_His') . "_create_{$tableName}_table.php");
                $generated |= $this->generateAndLog('migration', $migrationFile, $replacements);
            }
        }

        // Register binding if repository was generated
        if ($generated && ($this->option('repository') || $this->option('service') || $this->option('controller'))) {
            $this->registerBinding($modelName);
        }
    }

    /**
     * Helper to generate a file from a stub and output info or warning.
     *
     * @param string $stubName
     * @param string $filePath
     * @param array $replacements
     * @return bool True if file generated, false if already exists
     */
    protected function generateAndLog(string $stubName, string $filePath, array $replacements): bool
    {
        if ($this->generateFile($stubName, $filePath, $replacements)) {
            $this->info("Created {$stubName} file at {$filePath}");
            return true;
        } else {
            $this->warn("Skipped existing {$stubName} file: {$filePath}");
            return false;
        }
    }

    /**
     * Generate a single file based on a stub and replacements.
     *
     * @param string $stubName
     * @param string $path
     * @param array $replacements
     * @return bool True if file created, false if already exists
     */
    protected function generateFile(string $stubName, string $path, array $replacements): bool
    {
        if (file_exists($path)) {
            return false; // Skip if file exists
        }

        $stubFile = __DIR__ . "/../../templates/{$stubName}.stub";
        if (!file_exists($stubFile)) {
            $this->error("Stub file not found: {$stubFile}");
            return false;
        }

        $content = file_get_contents($stubFile);
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        file_put_contents($path, $content);

        return true;
    }

    /**
     * Register repository binding in AppServiceProvider.
     *
     * @param string $modelName
     * @return void
     */
    protected function registerBinding(string $modelName)
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');

        if (!file_exists($providerPath)) {
            $this->warn('AppServiceProvider.php not found. Cannot register repository binding.');
            return;
        }

        $content = file_get_contents($providerPath);

        $bindString = "        \$this->app->bind(\n            \\App\\Contracts\\RepositoryInterface::class,\n            \\App\\Repositories\\{$modelName}Repository::class\n        );";

        // Check if already registered
        if (strpos($content, $bindString) !== false) {
            $this->info("Repository binding for {$modelName} already registered.");
            return;
        }

        // Insert binding into the register method of AppServiceProvider
        $pattern = '/public function register\(\)\s*\{/';
        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = $matches[0][1] + strlen($matches[0][0]);
            $content = substr_replace($content, "\n" . $bindString . "\n", $pos, 0);
            file_put_contents($providerPath, $content);
            $this->info("Registered repository binding for {$modelName} in AppServiceProvider.");
        } else {
            $this->warn("Could not find register() method in AppServiceProvider to add repository binding.");
        }
    }

    /**
     * Add resource route to routes/api.php file.
     *
     * @param string $modelName
     * @param string $tableName
     * @return void
     */
    protected function addRouteResource(string $modelName, string $tableName)
    {
        $routesFile = base_path('routes/api.php');
        if (!file_exists($routesFile)) {
            $this->warn('routes/api.php file not found. Cannot add resource route.');
            return;
        }

        $routeLine = "Route::apiResource('{$tableName}', \\App\\Http\\Controllers\\{$modelName}Controller::class);";

        $routesContent = file_get_contents($routesFile);

        if (strpos($routesContent, $routeLine) !== false) {
            $this->info("API resource route for {$modelName} already exists in routes/api.php.");
            return;
        }

        // Append the route line at the end of routes/api.php
        file_put_contents($routesFile, PHP_EOL . $routeLine . PHP_EOL, FILE_APPEND);

        $this->info("Added API resource route for {$modelName} to routes/api.php.");
    }

    /**
     * Display final summary after generation.
     *
     * @param string $modelName
     * @return void
     */
    protected function displayGenerationSummary(string $modelName)
    {
        if ($this->allFilesGenerated) {
            $this->info("All files for {$modelName} generated successfully.");
            $this->info('Remember to run migrations and clear caches if needed.');
        } else {
            $this->warn('Some files were skipped because they already exist.');
        }
    }
}
