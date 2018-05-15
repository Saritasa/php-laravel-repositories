<?php

namespace Saritasa\LaravelRepositories\Exceptions;

use Saritasa\LaravelRepositories\Contracts\IRepository;
use Throwable;

/**
 * Thrown in repositories when model not found.
 *
 * @see IRepository
 */
class ModelNotFoundException extends RepositoryException
{
    /**
     * Model that was not found.
     *
     * @var string
     */
    protected $modelClass;

    /**
     * Id with which was an attempt to find model.
     *
     * @var int
     */
    protected $id;

    /**
     * Throws in repositories when model not found.
     *
     * @param IRepository $repository Repository which throws an exception.
     * @param string $id Requested identifier
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(IRepository $repository, string $id, ?Throwable $previous = null)
    {
        $this->modelClass = $repository->getModelClass();
        $this->id = $id;
        parent::__construct($repository, 'message', 404, $previous);
    }

    /**
     * Returns class of the model, that was not found
     *
     * @return string
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Returns requested identifier of model, that was not found
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
