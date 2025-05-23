<?php

namespace Packages\AhmedMahmoud\RepositoryPattern;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

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
