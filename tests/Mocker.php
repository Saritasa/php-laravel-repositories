<?php

namespace Saritasa\Repositories\Tests;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery\MockInterface;

/**
 * Unit tests mocking helper.
 */
class Mocker
{
    /**
     * Mocks Connection and connection resolver for testing repositories and models.
     *
     * @return MockInterface
     */
    public static function mockConnectionResolver(): MockInterface
    {
        $connectionMock = \Mockery::mock(ConnectionInterface::class);
        $connectionMock->shouldReceive('getQueryGrammar')->andReturn(new Grammar());
        $connectionMock->shouldReceive('getPostProcessor')->andReturn(new Processor());
        $resolver = \Mockery::mock(ConnectionResolver::class);
        $resolver->shouldReceive('connection')->andReturn($connectionMock);
        return $resolver;
    }
}
