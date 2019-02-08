<?php

namespace Saritasa\LaravelRepositories\Tests;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Saritasa\LaravelRepositories\DTO\Criterion;
use Saritasa\LaravelRepositories\Exceptions\RepositoryException;
use Saritasa\LaravelRepositories\Repositories\Repository;

/**
 * Tests of repository correct building nested queries.
 */
class NestedQueryBuilderTest extends TestCase
{
    /**
     * Tested repository.
     *
     * @var MockInterface|Repository
     */
    protected $repositoryMock;

    /**
     * Query builder instance.
     *
     * @var MockInterface|QueryBuilder
     */
    protected $queryMock;

    /**
     * Wrapper of query builder instance.
     *
     * @var MockInterface|EloquentBuilder
     */
    protected $builder;

    /**
     * Set up test parameters.
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock = Mockery::mock(Repository::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->queryMock = Mockery::mock(QueryBuilder::class);
        $this->builder = Mockery::mock(EloquentBuilder::class);
        $this->builder->shouldReceive('getQuery')->andReturn($this->queryMock);
        $this->repositoryMock->shouldReceive('query')->andReturn($this->builder);
    }

    /**
     * Tests that checks that top level criteria being processed correctly.
     *
     * @return void
     *
     * @throws InvalidArgumentException
     * @throws RepositoryException
     */
    public function testCriteriaBuildingWithOnlyTopLevelCriteria(): void
    {
        $nestedQueryMock = Mockery::mock(QueryBuilder::class);
        $nestedQueryMock->shouldReceive('where')->andReturnUsing(
            function (string $field, string $operator, $value, string $boolean) {
                $this->assertEquals('value1', $value);
                $this->assertEquals('field1', $field);
                $this->assertEquals('=', $operator);
                $this->assertEquals('and', $boolean);
            },
            function (string $field, string $operator, $value, string $boolean) {
                $this->assertEquals(false, $value);
                $this->assertEquals('field2', $field);
                $this->assertEquals('<>', $operator);
                $this->assertEquals('and', $boolean);
            },
            function (string $field, string $operator, $value, string $boolean) {
                $this->assertEquals(null, $value);
                $this->assertEquals('field3', $field);
                $this->assertEquals('>', $operator);
                $this->assertEquals('or', $boolean);
            },
            function (string $field, string $operator, $value, string $boolean) {
                $this->assertEquals(999, $value);
                $this->assertEquals('field4', $field);
                $this->assertEquals('<=', $operator);
                $this->assertEquals('and', $boolean);
            }
        );
        $nestedQueryMock->shouldReceive('whereIn')->andReturnUsing(
            function (string $field, array $values, string $boolean) {
                $this->assertEquals([1, 2], $values);
                $this->assertEquals('field5', $field);
                $this->assertEquals('or', $boolean);
            }
        );
        $this->queryMock->shouldReceive('forNestedWhere')->andReturn($nestedQueryMock);
        $this->builder->shouldReceive('addNestedWhereQuery')->withArgs([$nestedQueryMock])
            ->andReturnSelf();
        $this->builder->shouldReceive('first')->andReturnNull();
        $this->repositoryMock->findWhere([
            'field1' => 'value1',
            ['field2', '<>', false],
            ['field3', '>', null, 'or'],
            new Criterion([
                Criterion::OPERATOR => '<=',
                Criterion::VALUE => 999,
                Criterion::ATTRIBUTE => 'field4',
            ]),
            new Criterion([
                Criterion::OPERATOR => 'in',
                Criterion::VALUE => [1, 2],
                Criterion::ATTRIBUTE => 'field5',
                Criterion::BOOLEAN => 'or',
            ]),
        ]);
    }
}
