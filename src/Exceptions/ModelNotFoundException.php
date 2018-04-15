<?php

namespace Saritasa\Exceptions;

use Saritasa\Contracts\IRepository;
use Throwable;

/**
 * Throws in repositories when model not found.
 *
 * @see IRepository
 */
class ModelNotFoundException extends RepositoryException
{
    /**
     * Model which was not found.
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
     * ModelNotFoundException constructor.
     *
     * @param IRepository $repository
     * @param int $id
     * @param Throwable|null $previous
     */
    public function __construct(IRepository $repository, int $id, Throwable $previous = null)
    {
        $this->modelClass = $repository->getModelClass();
        $this->id = $id;
        parent::__construct($repository, 'message', 404, $previous);
    }

    /**
     * Return class of model which was not found.
     *
     * @return string
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Return id with which was an attempt to find model.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
