<?php

namespace Saritasa\LaravelRepositories\Repositories;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Saritasa\LaravelRepositories\Contracts\IRepository;
use Saritasa\LaravelRepositories\Contracts\IRepositoryFactory;
use Saritasa\LaravelRepositories\Exceptions\RepositoryRegisterException;

/**
 * {@inheritdoc}
 */
class RepositoryFactory implements IRepositoryFactory
{
    /**
     * Registered repositories.
     *
     * @var array
     */
    protected $registeredRepositories = [];

    /**
     * Already created instances.
     *
     * @var array
     */
    protected $sharedInstances = [];

    /**
     * DI container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * Builds repositories for managing models.
     *
     * @param Container $container DI container instance
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }


    /** {@inheritdoc} */
    public function getRepository(string $modelClass): IRepository
    {
        if (empty($this->sharedInstances[$modelClass])) {
            $this->sharedInstances[$modelClass] = $this->build($modelClass);
        }

        return $this->sharedInstances[$modelClass];
    }

    /**
     * Build repository by model class from registered instances or creates default.
     *
     * @param string $modelClass Model class
     *
     * @return IRepository
     *
     * @throws BindingResolutionException
     */
    protected function build(string $modelClass): IRepository
    {
        $repositoryClass = $this->registeredRepositories[$modelClass] ?? Repository::class;

        $parameters = [];

        if ($repositoryClass === Repository::class || is_subclass_of($repositoryClass, Repository::class)
        ) {
            $parameters = ['modelClass' => $modelClass];
        }

        return $this->container->make($repositoryClass, $parameters);
    }

    /** {@inheritdoc} */
    public function register(string $modelClass, string $repositoryClass): void
    {
        if (!is_subclass_of($modelClass, Model::class)) {
            throw new RepositoryRegisterException("$modelClass must extend " . Model::class);
        }
        if (!is_subclass_of($repositoryClass, IRepository::class)) {
            throw new RepositoryRegisterException("$repositoryClass must implement " . IRepository::class);
        }
        $this->registeredRepositories[$modelClass] = $repositoryClass;
    }
}
