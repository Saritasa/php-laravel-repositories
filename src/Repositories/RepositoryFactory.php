<?php

namespace Saritasa\LaravelRepositories\Repositories;

use Illuminate\Database\Eloquent\Model;
use Saritasa\LaravelRepositories\Exceptions\RepositoryException;
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
     * @throws RepositoryException
     */
    protected function build(string $modelClass): IRepository
    {
        if (isset($this->registeredRepositories[$modelClass])) {
            return new $this->registeredRepositories[$modelClass]($modelClass);
        }
        return new Repository($modelClass);
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
