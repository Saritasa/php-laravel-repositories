<?php

namespace Saritasa\LaravelRepositories\Repositories;

use Saritasa\LaravelRepositories\Exceptions\RepositoryException;
use Saritasa\LaravelRepositories\Contracts\IRepository;
use Saritasa\LaravelRepositories\Contracts\IRepositoryFactory;

/**
 * Factories for eloquent repositories.
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
    protected static $sharedInstances = [];

    /**
     * Returns needed repository for model class.
     *
     * @param string $modelClass Model class
     *
     * @return IRepository
     *
     * @throws RepositoryException
     */
    public function getRepository(string $modelClass): IRepository
    {
        if (!isset(static::$sharedInstances[$modelClass]) || static::$sharedInstances[$modelClass] === null) {
            static::$sharedInstances[$modelClass] = $this->build($modelClass);
        }
        return static::$sharedInstances[$modelClass];
    }

    /**
     * Build repository by model class from registered instances or creates default.
     *
     * @param string $modelClass Model class
     *
     * @return IRepository
     *
     * @throws RepositoryException
     */
    protected function build(string $modelClass): IRepository
    {
        if (isset($this->registeredServiceManagers[$modelClass])) {
            return new $this->registeredRepositories[$modelClass]($modelClass);
        }
        return new Repository($modelClass);
    }

    /**
     * Registered certain repository realization for model class.
     *
     * @param string $modelClass Model class
     * @param string $repositoryClass Repository realization class
     *
     * @return void
     */
    public function register(string $modelClass, string $repositoryClass): void
    {
        $this->registeredRepositories[$modelClass] = $repositoryClass;
    }
}
