<?php

namespace Saritasa\Exceptions;

use Saritasa\Repositories\Base\IRepository;
use Throwable;

/**
 * This exception must be thrown by repository, if no other exception class,
 * that describes situation better
 */
class RepositoryException extends \Exception
{
    public function __construct(IRepository $repository, $message = "", $code = 500, Throwable $previous = null)
    {
        $repoClass = get_class($repository);

        parent::__construct($repoClass.': '.$message, $code, $previous);
    }
}
