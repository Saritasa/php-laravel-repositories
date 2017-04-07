<?php

namespace App\Exceptions;

use Saritasa\Repositories\Base\IRepository;
use Exception;

/**
 * This exception must be thrown by repository, if no other exception class,
 * that describes situation better
 */
class RepositoryException extends \Exception
{
    public function __construct(IRepository $repository, $message = "", $code = 500, Exception $previous = null)
    {
        $repoClass = get_class($repository);

        parent::__construct($repoClass.': '.$message, $code, $previous);
    }
}