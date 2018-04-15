<?php

namespace Saritasa\Contracts;

use Saritasa\Exceptions\RepositoryException;

/**
 * Repositories factory.
 */
interface IRepositoryFactory
{
    /**
     * Returns needed repository for model class.
     *
     * @param string $modelClass Model class
     *
     * @return IRepository
     *
     * @throws RepositoryException
     */
    public function getRepository(string $modelClass): IRepository;

    /**
     * Registered certain repository realization for model class.
     *
     * @param string $modelClass Model class
     * @param string $repositoryClass Repository realization class
     *
     * @return void
     */
    public function register(string $modelClass, string $repositoryClass): void;
}
