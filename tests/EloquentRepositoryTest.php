<?php

namespace Saritasa\Repositories\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use Saritasa\DTO\SortOptions;
use Saritasa\Enums\OrderDirections;
use Saritasa\Repositories\Base\EloquentRepository;

/**
 * Check Eloquent repository.
 */
class EloquentRepositoryTest extends TestCase
{

    /** @var EloquentEntitiesRepository */
    private $repository;

    /**
     * Check Eloquent repository.
     *
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->repository = new EloquentEntitiesRepository();
    }

    public function setUp()
    {
        parent::setUp();
        $resolver = Mocker::mockConnectionResolver();
        Model::setConnectionResolver($resolver);
    }

    /**
     * Test that repository's getWith() method performs valid filtering.
     *
     * @return void
     */
    public function testGetWithWhere()
    {
        $whereConditions = [
            ['id', '=', 1],
            ['age', '>', 21],
        ];
        $expectedSql = CompanyTestModel::query()->where($whereConditions)->toSql();
        $actualSql = $this->repository->getWithQueryString([], [], $whereConditions);

        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test that repository's getWith() method performs valid sorting.
     *
     * @return void
     */
    public function testGetWithOrdering()
    {
        $sortOptions = new SortOptions('name', OrderDirections::DESC);

        $expectedSql = CompanyTestModel::query()->orderBy($sortOptions->orderBy, $sortOptions->sortOrder)->toSql();
        $actualSql = $this->repository->getWithQueryString([], [], [], $sortOptions);

        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test that repository's getWith() method performs valid related models eager loads.
     *
     * @return void
     */
    public function testGetWithEagerLoad()
    {
        $eagerLoadedRelations = ['role', 'cars'];
        $expectedBuilder = CompanyTestModel::query()->with($eagerLoadedRelations);
        $actualBuilder = $this->repository->getWithQueryBuilder($eagerLoadedRelations);

        $this->assertEquals($expectedBuilder->getEagerLoads(), $actualBuilder->getEagerLoads());
    }

    /**
     * Test that repository's getWith() method performs valid related models counts retrieving.
     *
     * @return void
     */
    public function testGetWithCounts()
    {
        $eagerLoadedRelationsCounts = ['persons'];
        $expectedSql = CompanyTestModel::query()->withCount($eagerLoadedRelationsCounts)->toSql();
        $actualSql = $this->repository->getWithQueryString([], $eagerLoadedRelationsCounts);

        $this->assertEquals($actualSql, $expectedSql);
    }
}

/**
 * Fake user model class. Used to build test repository and perform tests on this model.
 */
class CompanyTestModel extends Model
{
    protected $table = 'companies';

    public function persons()
    {
        return $this->hasMany(PersonsTestModel::class, 'company_id', 'id');
    }
}

/**
 * Fake user model class. Used to build test repository and perform tests on this model.
 */
class PersonsTestModel extends Model
{
    protected $table = 'persons';
}


/**
 * Fake company records eloquent repository class. Has debug methods for performing tests.
 */
class EloquentEntitiesRepository extends EloquentRepository
{
    protected $modelClass = CompanyTestModel::class;

    /**
     * Returns built SQL string for requested rules.
     *
     * @param array $with Which relations should be preloaded
     * @param array $withCounts Which related entities should be counted
     * @param array $where Conditions that retrieved entities should satisfy
     * @param SortOptions $sortOptions How list of item should be sorted
     *
     * @return string
     */
    public function getWithQueryString(
        array $with,
        array $withCounts = null,
        array $where = null,
        SortOptions $sortOptions = null
    ): string {
        return $this->getWithBuilder($with, $withCounts, $where, $sortOptions)->toSql();
    }

    /**
     * Returns builder for requested rules.
     *
     * @param array|null $with Which relations should be preloaded
     * @param array|null $withCounts Which related entities should be counted
     * @param array|null $where Conditions that retrieved entities should satisfy
     * @param null|SortOptions $sortOptions How list of item should be sorted
     *
     * @return Builder
     */
    public function getWithQueryBuilder(
        array $with,
        array $withCounts = null,
        array $where = null,
        SortOptions $sortOptions = null
    ): Builder {
        return $this->getWithBuilder($with, $withCounts, $where, $sortOptions);
    }
}
