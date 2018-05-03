<?php

namespace Saritasa\LaravelRepositories\Exceptions;

use Saritasa\LaravelRepositories\Contracts\IRepository;
use Throwable;

/**
 * Base exception for repository layer.
 */
class RepositoryException extends \Exception
{
    /**
     * Base exception for repository layer.
     *
     * @param IRepository $repository Repository which throws an exception.
     * @param string $message Exception message
     * @param int $code Exception code
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(IRepository $repository, $message = "", $code = 500, ?Throwable $previous = null)
    {
        $repoClass = get_class($repository);
        parent::__construct("$repoClass: $message", $code, $previous);
    }
}
