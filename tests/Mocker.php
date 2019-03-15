<?php

namespace Saritasa\LaravelRepositories\Tests;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery;
use Mockery\MockInterface;

/**
 * Unit tests mocking helper.
 */
class Mocker
{
    /**
     * Mocks connection for testing repositories and models.
     *
     * @return MockInterface|ConnectionInterface
     */
    public static function mockConnection(): MockInterface
    {
        $connectionMock = Mockery::mock(ConnectionInterface::class);
        $connectionMock->shouldReceive('getQueryGrammar')->andReturn(new Grammar());
        $connectionMock->shouldReceive('getPostProcessor')->andReturn(new Processor());

        return $connectionMock;
    }

    /**
     * Mocks connection resolver for testing repositories and models.
     *
     * @param ConnectionInterface $connection Connection to mock resolver
     *
     * @return MockInterface|ConnectionResolverInterface
     */
    public static function mockConnectionResolver(ConnectionInterface $connection): MockInterface
    {
        $resolver = Mockery::mock(ConnectionResolver::class);
        $resolver->shouldReceive('connection')->andReturn($connection);

        return $resolver;
    }
}
