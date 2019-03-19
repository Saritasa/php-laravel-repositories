<?php

namespace Saritasa\LaravelRepositories\Contracts;

use Illuminate\Contracts\Container\BindingResolutionException;
use Saritasa\LaravelRepositories\Exceptions\RepositoryException;
use Saritasa\LaravelRepositories\Exceptions\RepositoryRegisterException;

/**
 * Builds repositories for managing models.
 */
interface IRepositoryFactory
{
    /**
     * Returns repository that can manage given model class
     *
     * @param string $modelClass Model class that need to manage
     *
     * @return IRepository
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function getRepository(string $modelClass): IRepository;

    /**
     * Registers certain repository implementation for model class.
     *
     * @param string $modelClass Model class that needed certain repository
     * @param string $repositoryClass Repository realization class
     *
     * @return void
     *
     * @throws RepositoryRegisterException
     */
    public function register(string $modelClass, string $repositoryClass): void;
}
