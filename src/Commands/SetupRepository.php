<?php

namespace Packages\AhmedMahmoud\RepositoryPattern\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Class SetupRepository
 *
 * Artisan command to scaffold base files for the Repository pattern.
 * It creates the Contracts interface, BaseRepository, and the Service Provider.
 */
class SetupRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repository:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup repository pattern base files';

    /**
     * Filesystem instance for handling file operations.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * SetupRepository constructor.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->createContracts();
        $this->createRepositories();
        $this->createServiceProvider();

        $this->info('Repository pattern setup completed!');
    }

    /**
     * Create the Contracts directory and RepositoryInterface file.
     *
     * @return void
     */
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

    /**
     * Create the Repositories directory and BaseRepository file.
     *
     * @return void
     */
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

    /**
     * Create the RepositoriesServiceProvider if it doesn't already exist.
     *
     * @return void
     */
    protected function createServiceProvider()
    {
        $providerPath = app_path('Providers/RepositoriesServiceProvider.php');

        if (!file_exists($providerPath)) {
            $providerStub = $this->files->get(__DIR__ . '/../../templates/repositories-service-provider.stub');
            $this->files->put($providerPath, $providerStub);
        }
    }
}
