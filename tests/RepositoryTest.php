<?php

namespace Saritasa\Repositories\Tests;

use PHPUnit\Framework\TestCase;
use Saritasa\Exceptions\RepositoryException;
use Saritasa\Repositories\Base\Repository;

/**
 * Check base repository.
 */
class RepositoryTest extends TestCase
{
    /**
     * Checks that exception will thrown when model class is not defined.
     */
    public function testCreationWhenModelClassNotSet()
    {
        $this->expectException(RepositoryException::class);
        new class extends Repository {
            protected $modelClass;
        };
    }

    /**
     * Checks that exception will thrown when model class is not exists or invalid.
     */
    public function testCreationWhenModelClassInvalid()
    {
        $this->expectException(RepositoryException::class);
        new class extends Repository {
            protected $modelClass = 'not existing class';
        };
    }
}
