<?php

namespace Packages\AhmedMahmoud\RepositoryPattern;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;

/**
 * Class RepositoryServiceProvider
 *
 * Service provider to register repository commands and publish stub templates.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        // You can bind interfaces to implementations here if needed
    }

    /**
     * Bootstrap services.
     *
     * @param Filesystem $files
     * @return void
     */
    public function boot(Filesystem $files)
    {
        $this->publishes([
            __DIR__ . '/../templates' => resource_path('stubs/repository-pattern'),
        ], 'repository-stubs');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Packages\AhmedMahmoud\RepositoryPattern\Commands\MakeRepository::class,
                \Packages\AhmedMahmoud\RepositoryPattern\Commands\SetupRepository::class,
            ]);
        }
    }
}
