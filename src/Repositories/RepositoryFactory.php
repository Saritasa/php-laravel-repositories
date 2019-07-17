<?php

namespace Saritasa\LaravelRepositories\Repositories;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Saritasa\LaravelRepositories\Contracts\IEntity;
use Saritasa\LaravelRepositories\Contracts\IRepository;
use Saritasa\LaravelRepositories\Contracts\IRepositoryFactory;
use Saritasa\LaravelRepositories\Entities\EloquentEntity;
use Saritasa\LaravelRepositories\Exceptions\RepositoryRegisterException;
use Saritasa\LaravelRepositories\Repositories\Adapters\EloquentAdapterRepository;

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


    /**
     * {@inheritdoc}
     *
     * @throws RepositoryRegisterException
     */
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
     * @throws RepositoryRegisterException
     */
    protected function build(string $modelClass): IRepository
    {
        $repositoryClass = $this->registeredRepositories[$modelClass] ?? null;

        if (!$repositoryClass) {
            switch (true) {
                case $modelClass instanceof EloquentEntity:
                    $repositoryClass = EloquentAdapterRepository::class;
                    break;
                default:
                    throw new RepositoryRegisterException();
            }
        }

        $parameters = [];

        if ($repositoryClass === EloquentAdapterRepository::class
            || is_subclass_of($repositoryClass, EloquentAdapterRepository::class)
        ) {
            $parameters = ['modelClass' => $modelClass];
        }

        return $this->container->make($repositoryClass, $parameters);
    }

    /** {@inheritdoc} */
    public function register(string $modelClass, string $repositoryClass): void
    {
        if (!is_subclass_of($modelClass, IEntity::class)) {
            throw new RepositoryRegisterException("$modelClass must extend " . IEntity::class);
        }
        if (!is_subclass_of($repositoryClass, IRepository::class)) {
            throw new RepositoryRegisterException("$repositoryClass must implement " . IRepository::class);
        }
        $this->registeredRepositories[$modelClass] = $repositoryClass;
    }
}
