<?php

namespace Saritasa\LaravelRepositories\Tests;

use Exception;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Saritasa\DingoApi\Paging\PagingInfo;
use Saritasa\Exceptions\PagingException;
use Saritasa\LaravelRepositories\Exceptions\BadCriteriaException;
use Saritasa\LaravelRepositories\Exceptions\ModelNotFoundException;
use Saritasa\LaravelRepositories\Exceptions\RepositoryException;
use Saritasa\LaravelRepositories\Repositories\Repository;

/**
 * Tests of work repository class.
 */
class RepositoryTest extends TestCase
{
    /**
     * Connection to database mock.
     *
     * @var ConnectionInterface|MockInterface
     */
    protected $connection;

    /**
     * Sets up test config.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->connection = Mocker::mockConnection();
        Model::setConnectionResolver(Mocker::mockConnectionResolver($this->connection));
        $this->connection->shouldReceive('query')->andReturn(new Builder($this->connection));
        $this->connection->shouldReceive('getName');
    }

    /**
     * Checks that exception will thrown when model class is not exists or invalid.
     *
     * @dataProvider constructorInitialisationData
     *
     * @param string $className Class name which repository needs to build
     * @param string|null $exception Exception which should be thrown
     *
     * @return void
     *
     * @throws InvalidArgumentException
     * @throws RepositoryException
     */
    public function testConstructorMethod(string $className, ?string $exception): void
    {
        if ($exception) {
            $this->expectException($exception);
        }

        $repository = new Repository($className);
        $this->assertEquals($className, $repository->getModelClass());
    }

    /**
     * Returns data to test repository constructor.
     *
     * @return array
     */
    public function constructorInitialisationData(): array
    {
        return [
            ['', RepositoryException::class],
            ['className', RepositoryException::class],
            [get_class(new class{
            }), RepositoryException::class],
            [Repository::class, RepositoryException::class],
            [RepositoryException::class, RepositoryException::class],
            [Model::class, RepositoryException::class],
            [get_class(new class extends Model{
            }), null],
            [TestEntity::class, null],
        ];
    }

    /**
     * Tests that repository create method works correct.
     *
     * @dataProvider saveEntityData
     *
     * @param string $servedClass Served class by tested service
     * @param bool $createResult Shows whether creating on Eloquent side was correct or not
     * @param string|null $exception Exception which should be thrown
     *
     * @return void
     *
     * @throws RepositoryException
     * @throws InvalidArgumentException
     */
    public function testCreationMethod(string $servedClass, bool $createResult, ?string $exception): void
    {
        /**
         * Entity mock to create.
         *
         * @var Model|MockInterface $entity
         */
        $entity = Mockery::mock(TestEntity::class)->makePartial();
        $entity->shouldReceive('save')->andReturn($createResult);

        $repository = new Repository($servedClass);

        if ($exception) {
            $this->expectException($exception);
        }

        $actualEntity = $repository->create($entity);
        $this->assertSame($entity, $actualEntity);
    }

    /**
     * Tests that repository save method works correct.
     *
     * @dataProvider saveEntityData
     *
     * @param string $servedClass Served class by tested service
     * @param bool $createResult Shows whether creating on Eloquent side was correct or not
     * @param string|null $exception Exception which should be thrown
     *
     * @return void
     *
     * @throws RepositoryException
     * @throws InvalidArgumentException
     */
    public function testSaveMethod(string $servedClass, bool $createResult, ?string $exception): void
    {
        /**
         * Entity mock to save.
         *
         * @var Model|MockInterface $entity
         */
        $entity = Mockery::mock(TestEntity::class)->makePartial();
        $entity->shouldReceive('save')->andReturn($createResult);

        $repository = new Repository($servedClass);

        if ($exception) {
            $this->expectException($exception);
        }

        $actualEntity = $repository->save($entity);
        $this->assertSame($entity, $actualEntity);
    }

    /**
     * Returns different params for create/save entity methods.
     *
     * @return array
     */
    public function saveEntityData(): array
    {
        $servedEntity = get_class(new class extends Model{
        });

        return [
            [$servedEntity, false, RepositoryException::class],
            [$servedEntity, true, RepositoryException::class],
            [TestEntity::class, false, RepositoryException::class],
            [TestEntity::class, true, null],
        ];
    }

    /**
     * Tests that repository save method works correct.
     *
     * @dataProvider deleteEntityData
     *
     * @param string $servedClass Served class by tested service
     * @param bool $createResult Shows whether creating on Eloquent side was correct or not
     * @param bool $isExceptionOnEloquentSide Shows whether exception should be thrown on Eloquent side
     * @param string|null $exception Exception which should be thrown
     *
     * @return void
     *
     * @throws RepositoryException
     */
    public function testDeleteMethod(
        string $servedClass,
        bool $createResult,
        bool $isExceptionOnEloquentSide,
        string $exception
    ): void {
        /**
         * Entity mock to delete.
         *
         * @var Model|MockInterface $entity
         */
        $entity = Mockery::mock(TestEntity::class)->makePartial();
        if ($isExceptionOnEloquentSide) {
            $entity->shouldReceive('delete')->andThrow(new Exception());
        } else {
            $entity->shouldReceive('delete')->andReturn($createResult);
        }

        $repository = new Repository($servedClass);

        $this->expectException($exception);

        $repository->delete($entity);
    }

    /**
     * Returns different params for delete entity method.
     *
     * @return array
     */
    public function deleteEntityData(): array
    {
        $servedEntity = get_class(new class extends Model{
        });

        return [
            [$servedEntity, false, false, RepositoryException::class],
            [$servedEntity, true, false, RepositoryException::class],
            [$servedEntity, true, true, RepositoryException::class],
            [TestEntity::class, false, false, RepositoryException::class],
            [TestEntity::class, false, true, RepositoryException::class],
            [TestEntity::class, true, true, RepositoryException::class],
        ];
    }

    /**
     * Tests that repository returns validation rules from model.
     *
     * @return void
     *
     * @throws RepositoryException
     * @throws InvalidArgumentException
     */
    public function testGetValidationRulesWhenItStoresInModelMethod(): void
    {
        $servedEntity = new class extends Model {
            public function getValidationRules(): array
            {
                return ['rule1' => ['required', 'string', 'min:1']];
            }
        };

        $repository = new Repository(get_class($servedEntity));
        $actualValidationRules = $repository->getModelValidationRules();
        $this->assertEquals(['rule1' => ['required', 'string', 'min:1']], $actualValidationRules);
    }

    /**
     * Tests that get method returns correct results.
     *
     * @return void
     *
     * @throws RepositoryException
     * @throws InvalidArgumentException
     */
    public function testGetMethod(): void
    {
        $field1 = 'string';
        $field2 = 2;
        $field3 = false;

        $results = [[
            TestEntity::FIELD_2 => $field2,
            TestEntity::FIELD_1 => $field1,
            TestEntity::FIELD_3 => $field3,
        ]];

        $this->connection->shouldReceive('select')->andReturn($results);

        $repository = new Repository(TestEntity::class);
        $actualResults = $repository->get();

        $this->assertEquals(1, $actualResults->count());
        /**
         * Actual entity to check.
         *
         * @var Model $actualEntity
         */
        $actualEntity = $actualResults->first();

        $this->assertEquals(TestEntity::class, get_class($actualEntity));
        $this->assertEquals($field1, $actualEntity->getAttribute(TestEntity::FIELD_1));
        $this->assertEquals($field2, $actualEntity->getAttribute(TestEntity::FIELD_2));
        $this->assertEquals($field3, $actualEntity->getAttribute(TestEntity::FIELD_3));
    }

    /**
     * Test findOrFail repository method.
     *
     * @dataProvider getFindOrFailData
     *
     * @param mixed $id Id to find entity
     * @param string $entityClass Served by repository entity class
     * @param bool $isExceptionOnEloquentSide Shows whether exception should be thrown on Eloquent side
     * @param string|null $exception Exception which should be thrown
     *
     * @return void
     *
     * @throws ModelNotFoundException
     * @throws RepositoryException
     * @throws InvalidArgumentException
     */
    public function testFindOrFailMethod(
        $id,
        string $entityClass,
        bool $isExceptionOnEloquentSide,
        ?string $exception
    ): void {
        if ($isExceptionOnEloquentSide) {
            $this->connection->shouldReceive('select')->andReturn([]);
        } else {
            $this->connection->shouldReceive('select')->andReturn([['id' => $id]]);
        }

        if ($exception) {
            $this->expectException($exception);
        }

        $repository = new Repository($entityClass);
        $actualEntity = $repository->findOrFail($id);

        $this->assertEquals($entityClass, get_class($actualEntity));
        $this->assertEquals($actualEntity->getAttribute('id'), $id);
    }

    /**
     * Returns different params for findOrFail repository method.
     *
     * @return array
     */
    public function getFindOrFailData(): array
    {
        $entityWithStringKeyType = get_class(new class extends Model {
            protected $keyType = 'string';
        });

        return [
            [37, TestEntity::class, false, null],
            [0, TestEntity::class, false, null],
            [-100, TestEntity::class, false, null],
            [-100, $entityWithStringKeyType, false, RepositoryException::class],
            [0, $entityWithStringKeyType, false, RepositoryException::class],
            [37, $entityWithStringKeyType, true, RepositoryException::class],
            ['string', $entityWithStringKeyType, false, null],
            ['', $entityWithStringKeyType, false, RepositoryException::class],
            ['', TestEntity::class, true, RepositoryException::class],
            [0xd, TestEntity::class, false, null],
            [0xd, $entityWithStringKeyType, false, RepositoryException::class],
            [100.01, TestEntity::class, false, RepositoryException::class],
            [100.01, $entityWithStringKeyType, false, null],
            [null, $entityWithStringKeyType, false, RepositoryException::class],
            [null, TestEntity::class, false, RepositoryException::class],
            [37, TestEntity::class, true, ModelNotFoundException::class],
            [-100, TestEntity::class, true, ModelNotFoundException::class],
            ['string', $entityWithStringKeyType, true, ModelNotFoundException::class],
            ['', $entityWithStringKeyType, true, RepositoryException::class],
        ];
    }

    /**
     * Tests that count method returns correct data.
     *
     * @dataProvider getCountData
     *
     * @param int $resultCount Expected result of count method
     * @param array $whereConditions Where conditions filters data
     * @param string $expectedQuery Expected query to DB without inserted values
     * @param array $expectedBindings Expected bindings which should be inserted in query
     *
     * @return void
     *
     * @throws RepositoryException
     * @throws BadCriteriaException
     * @throws InvalidArgumentException
     */
    public function testCountMethod(
        int $resultCount,
        array $whereConditions,
        string $expectedQuery,
        array $expectedBindings
    ): void {
        $this->connection
            ->shouldReceive('select')
            ->andReturnUsing(
                function (string $query, array $bindings) use ($resultCount, $expectedBindings, $expectedQuery) {
                    $this->assertEquals($expectedBindings, $bindings);
                    $this->assertEquals($expectedQuery, $query);

                    return [['aggregate' => $resultCount]];
                }
            );

        $repository = new Repository(TestEntity::class);
        $actualCount = $repository->count($whereConditions);
        $this->assertEquals($resultCount, $actualCount);
    }

    /**
     * Returns data to test count method.
     *
     * @return array
     */
    public function getCountData(): array
    {
        return [
            [
                0,
                ['f1' => 67, 'f2' => 's'],
                'select count(*) as aggregate from "test_table" where ("f1" = ? and "f2" = ?)',
                [67, 's'],
            ],
            [
                4,
                [['f1', '<>', 's'], ['f2', '>', 100]],
                'select count(*) as aggregate from "test_table" where ("f1" <> ? and "f2" > ?)',
                ['s', 100],
            ],
            [
                1,
                [],
                'select count(*) as aggregate from "test_table"',
                [],
            ],
            [
                100,
                [['f1', 'like', 's'], ['f2', 'in', [20, 30], 'or']],
                'select count(*) as aggregate from "test_table" where ("f1" like ? or "f2" in (?, ?))',
                ['s', 20, 30],
            ],
            [
                67,
                [[['f1', '<', 10], ['f1', '>', 100]], [['f2', 'not in', ['s', 'l']]]],
                'select count(*) as aggregate from "test_table" ' .
                'where (("f1" < ? and "f1" > ?) and ("f2" not in (?, ?)))',
                [10, 100, 's', 'l'],
            ],
        ];
    }

    /**
     * Tests that getWhere method returns correct data.
     *
     * @dataProvider getWhereData
     *
     * @param array $resultsData Data which should be returned from query to DB
     * @param array $whereConditions Where conditions filters data
     * @param string $expectedQuery Expected query to DB without inserted values
     * @param array $expectedBindings Expected bindings which should be inserted in query
     *
     * @return void
     *
     * @throws BadCriteriaException
     * @throws RepositoryException
     * @throws InvalidArgumentException
     */
    public function testGetWhereMethod(
        array $resultsData,
        array $whereConditions,
        string $expectedQuery,
        array $expectedBindings
    ): void {
        $this->connection
            ->shouldReceive('select')
            ->andReturnUsing(
                function (string $query, array $bindings) use ($resultsData, $expectedBindings, $expectedQuery) {
                    $this->assertEquals($expectedBindings, $bindings);
                    $this->assertEquals($expectedQuery, $query);

                    return $resultsData;
                }
            );

        $repository = new Repository(TestEntity::class);

        /**
         * Models collection returned from DB.
         *
         * @var Model[] $actualResults
         */
        $actualResults = $repository->getWhere($whereConditions);
        $this->assertEquals(count($resultsData), count($actualResults));
        foreach ($actualResults as $count => $entity) {
            $this->assertEquals(TestEntity::class, get_class($entity));
            $this->assertEquals($resultsData[$count], $entity->getAttributes());
        }
    }

    /**
     * Returns data to test get where method.
     *
     * @return array
     */
    public function getWhereData(): array
    {
        return [
            [
                [['id' => 4, 'h7' => 'yyy'], ['id' => 1, 'h7' => 'kkk']],
                ['f1' => 67, 'f2' => 's'],
                'select * from "test_table" where ("f1" = ? and "f2" = ?)',
                [67, 's'],
            ],
            [
                [['id' => 4], ['id' => 99]],
                [['f1', '<>', '2018-01-12'], ['f2', '>', 100]],
                'select * from "test_table" where ("f1" <> ? and "f2" > ?)',
                ['2018-01-12', 100],
            ],
            [
                [],
                [],
                'select * from "test_table"',
                [],
            ],
            [
                [['f2' => null, 'f0' => 0.008]],
                [['f1', 'like', 's'], ['f2', 'in', [20, 30], 'or']],
                'select * from "test_table" where ("f1" like ? or "f2" in (?, ?))',
                ['s', 20, 30],
            ],
            [
                [['g' => '2019-03-15', 'lll' => 114], ['g' => '2016-09-30', 'lll' => 0]],
                [[['f1', '=', 88], ['f1', '<>', 44]], [['f2', 'in', ['s', 'l']]]],
                'select * from "test_table" where (("f1" = ? and "f1" <> ?) and ("f2" in (?, ?)))',
                [88, 44, 's', 'l'],
            ],
            [
                [],
                [['f1', '>', '2019-01-05'], ['f1', '<', '2019-01-06', 'or']],
                'select * from "test_table" where ("f1" > ? or "f1" < ?)',
                ['2019-01-05', '2019-01-06'],
            ],
        ];
    }

    /**
     * Tests that findWhere method returns correct data.
     *
     * @dataProvider findWhereData
     *
     * @param array $resultsData Data which should be returned from query to DB
     * @param array $whereConditions Where conditions filters data
     * @param string $expectedQuery Expected query to DB without inserted values
     * @param array $expectedBindings Expected bindings which should be inserted in query
     *
     * @return void
     *
     * @throws BadCriteriaException
     * @throws RepositoryException
     * @throws InvalidArgumentException
     */
    public function testFindWhereMethod(
        array $resultsData,
        array $whereConditions,
        string $expectedQuery,
        array $expectedBindings
    ): void {
        $this->connection
            ->shouldReceive('select')
            ->andReturnUsing(
                function (string $query, array $bindings) use ($resultsData, $expectedBindings, $expectedQuery) {
                    $this->assertEquals($expectedBindings, $bindings);
                    $this->assertEquals($expectedQuery, $query);

                    return [$resultsData];
                }
            );

        $repository = new Repository(TestEntity::class);

        /**
         * Models collection returned from DB.
         *
         * @var Model[] $actualResults
         */
        $actualResult = $repository->findWhere($whereConditions);
        $this->assertEquals(TestEntity::class, get_class($actualResult));
        $this->assertEquals($resultsData, $actualResult->getAttributes());
    }

    /**
     * Returns data to test find where method.
     *
     * @return array
     */
    public function findWhereData(): array
    {
        return [
            [
                ['id' => 4, 'h7' => 'yyy'],
                ['f1' => 67, 'f2' => 's'],
                'select * from "test_table" where ("f1" = ? and "f2" = ?) limit 1',
                [67, 's'],
            ],
            [
                ['id' => 99],
                [['f1', '<>', '2018-01-12'], ['f2', '>', 100]],
                'select * from "test_table" where ("f1" <> ? and "f2" > ?) limit 1',
                ['2018-01-12', 100],
            ],
            [
                [],
                [],
                'select * from "test_table" limit 1',
                [],
            ],
            [
                ['f2' => null, 'f0' => 0.008],
                [['f1', 'like', 's'], ['f2', 'in', [20, 30], 'or']],
                'select * from "test_table" where ("f1" like ? or "f2" in (?, ?)) limit 1',
                ['s', 20, 30],
            ],
            [
                ['g' => '2019-03-15', 'lll' => 114],
                [[['f1', '=', 88], ['f1', '<>', 44]], [['f2', 'in', ['s', 'l']]]],
                'select * from "test_table" where (("f1" = ? and "f1" <> ?) and ("f2" in (?, ?))) limit 1',
                [88, 44, 's', 'l'],
            ],
            [
                [],
                [['f1', '>', '2019-01-05'], ['f1', '<', '2019-01-06', 'or']],
                'select * from "test_table" where ("f1" > ? or "f1" < ?) limit 1',
                ['2019-01-05', '2019-01-06'],
            ],
        ];
    }

    /**
     * Tests that getPage method returns correct data.
     *
     * @dataProvider getPageData
     *
     * @param array $resultsData Data which should be returned from query to DB
     * @param array $pagingData Pagination information
     * @param array $whereConditions Where conditions filters data
     * @param string $expectedCountQuery Expected query to DB which checks counts needed rows before main query
     * @param string|null $expectedQuery Expected query to DB without inserted values
     * @param array $expectedBindings Expected bindings which should be inserted in query
     *
     * @return void
     *
     * @throws BadCriteriaException
     * @throws InvalidArgumentException
     * @throws RepositoryException
     * @throws PagingException
     */
    public function testGetPageMethod(
        array $resultsData,
        array $pagingData,
        array $whereConditions,
        string $expectedCountQuery,
        ?string $expectedQuery,
        array $expectedBindings
    ): void {
        /**
         * Paging info mock.
         *
         * @var PagingInfo|MockInterface $pagingInfo
         */
        $pagingInfo = Mockery::mock(PagingInfo::class)->makePartial();
        if (isset($pagingData['page'])) {
            $pagingInfo->__set('page', $pagingData['page']);
        }
        if (isset($pagingData['pageSize'])) {
            $pagingInfo->__set('pageSize', $pagingData['pageSize']);
        }

        $this->connection
            ->shouldReceive('select')
            ->times(count($resultsData) ? 2 : 1)
            ->andReturnUsing(
                function (string $query, array $bindings) use ($resultsData, $expectedBindings, $expectedCountQuery) {
                    $this->assertEquals($expectedBindings, $bindings);
                    $this->assertEquals($expectedCountQuery, $query);

                    return [['aggregate' => count($resultsData)]];
                },
                function (string $query, array $bindings) use ($resultsData, $expectedBindings, $expectedQuery) {
                    $this->assertEquals($expectedBindings, $bindings);
                    $this->assertEquals($expectedQuery, $query);

                    return $resultsData;
                }
            );

        $repository = new Repository(TestEntity::class);

        $paginator = $repository->getPage($pagingInfo, $whereConditions);
        $this->assertEquals(count($resultsData), $paginator->total());

        /**
         * Models collection returned from DB.
         *
         * @var Model[] $entities
         */
        $entities = $paginator->items();
        foreach ($entities as $count => $entity) {
            $this->assertEquals(TestEntity::class, get_class($entity));
            $this->assertEquals($resultsData[$count], $entity->getAttributes());
        }
    }

    /**
     * Returns data to test get page method.
     *
     * @return array
     */
    public function getPageData(): array
    {
        return [
            [
                [['id' => 4, 'h7' => 'yyy'], ['id' => 1, 'h7' => 'kkk']],
                ['pageSize' => 99, 'page' => 67],
                ['f1' => 67, 'f2' => 's'],
                'select count(*) as aggregate from "test_table" where ("f1" = ? and "f2" = ?)',
                'select * from "test_table" where ("f1" = ? and "f2" = ?) limit 99 offset 6534',
                [0 => 67, 1 => 's'],
            ],
            [
                [['id' => 4], ['id' => 99]],
                ['pageSize' => 99],
                [['f1', '<>', '2018-01-12'], ['f2', '>', 100]],
                'select count(*) as aggregate from "test_table" where ("f1" <> ? and "f2" > ?)',
                'select * from "test_table" where ("f1" <> ? and "f2" > ?) limit 99 offset 0',
                ['2018-01-12', 100],
            ],
            [
                [],
                [],
                [],
                'select count(*) as aggregate from "test_table"',
                null,
                [],
            ],
            [
                [['f2' => null, 'f0' => 0.008]],
                ['page' => 345],
                [['f1', 'like', 's'], ['f2', 'in', [20, 30], 'or']],
                'select count(*) as aggregate from "test_table" where ("f1" like ? or "f2" in (?, ?))',
                'select * from "test_table" where ("f1" like ? or "f2" in (?, ?)) limit 15 offset 5160',
                ['s', 20, 30],
            ],
        ];
    }
}
