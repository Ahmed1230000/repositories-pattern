<?php

namespace Packages\AhmedMahmoud\RepositoryPattern\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class MakeRepositoryTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Packages\AhmedMahmoud\RepositoryPattern\RepositoryServiceProvider::class,
        ];
    }

    public function test_repository_setup_command_creates_files()
    {
        Artisan::call('repository:setup');

        $this->assertTrue(File::exists(app_path('Contracts/RepositoryInterface.php')));
        $this->assertTrue(File::exists(app_path('Repositories/BaseRepository.php')));
    }

    public function test_make_repository_command_all_option()
    {
        Artisan::call('repository:setup');
        Artisan::call('make:repo', ['name' => 'TestEntity', '--all' => true]);

        $this->assertTrue(File::exists(app_path('Models/TestEntity.php')));
        $this->assertTrue(File::exists(app_path('Http/Controllers/TestEntityController.php')));
        $this->assertTrue(File::exists(app_path('Http/Requests/TestEntityStoreFormRequest.php')));
        $this->assertTrue(File::exists(app_path('Http/Requests/TestEntityUpdateFormRequest.php')));
        $this->assertTrue(File::exists(app_path('Services/TestEntityService.php')));
        $this->assertTrue(File::exists(app_path('Repositories/TestEntityRepository.php')));
        $this->assertTrue(File::exists(app_path('Providers/RepositoriesServiceProvider.php')));

        $migrationFiles = File::files(database_path('migrations'));
        $migrationCreated = false;
        foreach ($migrationFiles as $file) {
            if (str_contains($file->getFilename(), 'create_test_entities_table')) {
                $migrationCreated = true;
                break;
            }
        }
        $this->assertTrue($migrationCreated, 'Migration file was not created.');

        $repoContent = File::get(app_path('Repositories/TestEntityRepository.php'));
        $this->assertFalse(str_contains($repoContent, '{{ModelName}}'), 'Placeholder {{ModelName}} was not replaced.');

        $controllerContent = File::get(app_path('Http/Controllers/TestEntityController.php'));
        $this->assertTrue(str_contains($controllerContent, 'use ApiResponse, HandleError;'), 'Controller does not use ApiResponse and HandleError traits.');
        $this->assertTrue(str_contains($controllerContent, 'return $this->success'), 'Controller does not use success method.');

        $serviceContent = File::get(app_path('Services/TestEntityService.php'));
        $this->assertTrue(str_contains($serviceContent, 'use HandleError;'), 'Service does not use HandleError trait.');
        $this->assertTrue(str_contains($serviceContent, '$this->handleError'), 'Service does not use handleError method.');

        $providerContent = File::get(app_path('Providers/RepositoriesServiceProvider.php'));
        $this->assertTrue(str_contains($providerContent, 'App\Services\TestEntityService::class'), 'Provider does not contain correct binding.');
    }

    public function test_make_repository_command_model_only()
    {
        Artisan::call('repository:setup');
        Artisan::call('make:repo', ['name' => 'TestEntity', '--model' => true]);

        $this->assertTrue(File::exists(app_path('Models/TestEntity.php')));
        $this->assertFalse(File::exists(app_path('Repositories/TestEntityRepository.php')));
        $this->assertFalse(File::exists(app_path('Providers/RepositoriesServiceProvider.php')));
    }

    public function test_make_repository_command_fails_without_options()
    {
        Artisan::call('repository:setup');
        $output = Artisan::call('make:repo', ['name' => 'TestEntity']);
        $this->assertEquals(1, $output);
        $this->assertStringContainsString('No options specified for make:repo command', Artisan::output());
        $this->assertStringContainsString('Example: php artisan make:repo Product --all', Artisan::output());
    }

    public function test_make_repository_fails_without_setup()
    {
        File::deleteDirectory(app_path('Contracts'));
        File::deleteDirectory(app_path('Repositories'));

        $output = Artisan::call('make:repo', ['name' => 'TestEntity']);
        $this->assertEquals(1, $output);
        $this->assertStringContainsString('Please run "php artisan repository:setup"', Artisan::output());
    }

    public function test_make_repository_command_does_not_overwrite_existing_files()
    {
        Artisan::call('repository:setup');
        Artisan::call('make:repo', ['name' => 'TestEntity', '--all' => true]);

        // Modify a file to test if it gets overwritten
        $controllerPath = app_path('Http/Controllers/TestEntityController.php');
        File::append($controllerPath, "\n// Test modification");

        // Run the command again
        Artisan::call('make:repo', ['name' => 'TestEntity', '--all' => true]);

        $controllerContent = File::get($controllerPath);
        $this->assertTrue(str_contains($controllerContent, '// Test modification'), 'Existing file was overwritten.');

        // Check that no new migration was created if table exists
        $migrationFilesBefore = File::files(database_path('migrations'));
        Artisan::call('migrate'); // Create the table
        Artisan::call('make:repo', ['name' => 'TestEntity', '--all' => true]);
        $migrationFilesAfter = File::files(database_path('migrations'));
        $this->assertCount(count($migrationFilesBefore), $migrationFilesAfter, 'New migration was created when table exists.');
    }

    public function test_make_repository_command_does_not_create_duplicate_migration()
    {
        Artisan::call('repository:setup');
        Artisan::call('make:repo', ['name' => 'TestEntity', '--all' => true]);

        // Get existing migrations without running migrate
        $migrationFilesBefore = File::files(database_path('migrations'));

        // Run the command again without migrating
        Artisan::call('make:repo', ['name' => 'TestEntity', '--all' => true]);

        // Check that no new migration was created
        $migrationFilesAfter = File::files(database_path('migrations'));
        $this->assertCount(count($migrationFilesBefore), $migrationFilesAfter, 'New migration was created when migration file exists.');

        // Now run migrate and try again
        Artisan::call('migrate');
        $migrationFilesBefore = File::files(database_path('migrations'));
        Artisan::call('make:repo', ['name' => 'TestEntity', '--all' => true]);
        $migrationFilesAfter = File::files(database_path('migrations'));
        $this->assertCount(count($migrationFilesBefore), $migrationFilesAfter, 'New migration was created when table exists.');
    }

    public function test_make_repository_command_no_files_created_message()
    {
        Artisan::call('repository:setup');
        Artisan::call('make:repo', ['name' => 'TestEntity', '--all' => true]);

        // Run the command again
        Artisan::call('make:repo', ['name' => 'TestEntity', '--all' => true]);

        $this->assertStringContainsString('No new files were created for TestEntity as all files and migration already exist.', Artisan::output());
        $this->assertStringNotContainsString('Repository pattern files for TestEntity created successfully!', Artisan::output());
    }

    public function test_make_repository_command_controller_creates_service()
    {
        Artisan::call('repository:setup');
        Artisan::call('make:repo', ['name' => 'TestEntity', '--controller' => true]);

        $this->assertTrue(File::exists(app_path('Http/Controllers/TestEntityController.php')), 'Controller was not created.');
        $this->assertTrue(File::exists(app_path('Services/TestEntityService.php')), 'Service was not created with controller.');
        $this->assertFalse(File::exists(app_path('Models/TestEntity.php')), 'Model was created unexpectedly.');
        $this->assertFalse(File::exists(app_path('Repositories/TestEntityRepository.php')), 'Repository was created unexpectedly.');
    }
}