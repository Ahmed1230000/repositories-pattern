<?php

namespace Packages\AhmedMahmoud\RepositoryPattern\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class SetupRepository extends Command
{
    protected $signature = 'repository:setup';
    protected $description = 'Setup repository pattern base files';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $this->createContracts();
        $this->createRepositories();
        $this->createServiceProvider();

        $this->info('Repository pattern setup completed!');
    }

    protected function createContracts()
    {
        $contractsDir = app_path('Contracts');
        $interfacePath = "{$contractsDir}/RepositoryInterface.php";

        if (!$this->files->isDirectory($contractsDir)) {
            $this->files->makeDirectory($contractsDir, 0755, true);
        }

        $interfaceStub = $this->files->get(__DIR__ . '/../../templates/repository-interface.stub');
        $this->files->put($interfacePath, $interfaceStub);
    }

    protected function createRepositories()
    {
        $repositoriesDir = app_path('Repositories');
        $baseRepoPath = "{$repositoriesDir}/BaseRepository.php";

        if (!$this->files->isDirectory($repositoriesDir)) {
            $this->files->makeDirectory($repositoriesDir, 0755, true);
        }

        $baseRepoStub = $this->files->get(__DIR__ . '/../../templates/base-repository.stub');
        $this->files->put($baseRepoPath, $baseRepoStub);
    }

    protected function createServiceProvider()
    {
        $providerPath = app_path('Providers/RepositoriesServiceProvider.php');

        if (!file_exists($providerPath)) {
            $providerStub = $this->files->get(__DIR__ . '/../../templates/repositories-service-provider.stub');
            $this->files->put($providerPath, $providerStub);
        }
    }
}
