<?php

namespace Saritasa\LaravelRepositories\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use PHPUnit\Framework\TestCase;
use Saritasa\Exceptions\NotImplementedException;
use Saritasa\LaravelRepositories\Exceptions\RepositoryException;
use Saritasa\LaravelRepositories\Repositories\Repository;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * Check join relation repository method.
 */
class JoinRelationTest extends TestCase
{
    /**
     * Entity repository to test.
     *
     * @var EntityRepository
     */
    protected $repository;

    /**
     * Check join relation repository method.
     *
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     *
     * @throws RepositoryException
     */
    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->repository = new EntitiesRepository();
    }

    /**
     * Set up test parameters.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $resolver = Mocker::mockConnectionResolver();
        Model::setConnectionResolver($resolver);
    }

    /**
     * Test base query retrieving.
     *
     * @throws InvalidArgumentException
     */
    public function testBaseQuery(): void
    {
        $expectedSql = User::query()->toSql();
        $simpleSql = strtolower($this->repository->getBaseQueryString());

        $this->assertEquals($simpleSql, $expectedSql);
    }

    /**
     * Test BelongsTo relation joining.
     *
     * @throws NotImplementedException
     * @throws InvalidArgumentException
     */
    public function testBelongsToJoin(): void
    {
        $actualSql = strtolower($this->repository->getQueryStringWithJoin('role'));
        $expectedSql = User::query()->leftJoin('roles', 'users.role_id', '=', 'roles.id')->toSql();

        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test HasMany relation joining.
     *
     * @throws NotImplementedException
     * @throws InvalidArgumentException
     */
    public function testHasManyJoin(): void
    {
        $actualSql = strtolower($this->repository->getQueryStringWithJoin('cars'));
        $expectedSql = User::query()->leftJoin('cars', 'cars.user_id', '=', 'users.id')->toSql();

        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test HasOne relation joining.
     *
     * @throws NotImplementedException
     * @throws InvalidArgumentException
     */
    public function testHasOneJoin(): void
    {
        $actualSql = strtolower($this->repository->getQueryStringWithJoin('profile'));
        $expectedSql = User::query()->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')->toSql();

        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test HasManyThrough relation joining.
     *
     * @throws NotImplementedException
     * @throws InvalidArgumentException
     */
    public function testHasManyThroughJoin(): void
    {
        $actualSql = strtolower($this->repository->getQueryStringWithJoin('supervisors'));
        $expectedSql = User::query()
            ->leftJoin('supervisors_users', 'supervisors_users.user_id', '=', 'users.id')
            ->leftJoin('supervisors', 'supervisors_users.supervisor_id', '=', 'supervisors.id')
            ->toSql();

        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test joining relations of relations.
     *
     * @throws NotImplementedException
     * @throws InvalidArgumentException
     */
    public function testNestedRelationsJoin(): void
    {
        $actualSql = strtolower($this->repository->getQueryStringWithJoin('profile.phones'));
        $expectedSql = User::query()
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('phones', 'phones.profile_id', '=', 'profiles.id')
            ->toSql();

        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test of multiple relations joining.
     *
     * @throws NotImplementedException
     * @throws InvalidArgumentException
     */
    public function testMultipleRelationsJoin(): void
    {
        $actualSql = strtolower($this->repository->getQueryStringWithJoin(['profile', 'cars']));
        $expectedSql = User::query()
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('cars', 'cars.user_id', '=', 'users.id')
            ->toSql();

        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test that same relation joined only once.
     *
     * @throws NotImplementedException
     * @throws InvalidArgumentException
     */
    public function testSingleJoin(): void
    {
        $actualSql = strtolower($this->repository->getQueryStringWithJoin(['profile.phones', 'profile']));
        $expectedSql = User::query()
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('phones', 'phones.profile_id', '=', 'profiles.id')
            ->toSql();

        $this->assertEquals($expectedSql, $actualSql);

        $actualSql = strtolower($this->repository->getQueryStringWithJoin(['profile', 'profile.phones']));
        $expectedSql = User::query()
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('phones', 'phones.profile_id', '=', 'profiles.id')
            ->toSql();

        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test that unsupported relations types are throws valid exception.
     *
     * @throws NotImplementedException
     */
    public function testUnsupportedJoin(): void
    {
        self::expectException(NotImplementedException::class);
        // Try to join polymorphic relation
        $this->repository->getQueryStringWithJoin('notes');
    }

    /**
     * Not declared in model relation method throws exception.
     *
     * @throws NotImplementedException
     */
    public function testUnknownJoin(): void
    {
        self::expectException(RelationNotFoundException::class);
        $this->repository->getQueryStringWithJoin('potato');
    }
}

/**
 * Fake user model class. Used to build test repository and perform tests on this model.
 */
class User extends Model
{
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function supervisors(): BelongsToMany
    {
        return $this->belongsToMany(Supervisor::class, 'supervisors_users');
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }
}

/**
 * User role model.
 */
class Role extends Model
{
}

/**
 * Car that can be owned by user.
 */
class Car extends Model
{
}

/**
 * User personal information profile.
 */
class Profile extends Model
{
    public function phones(): HasMany
    {
        return $this->hasMany(Phone::class);
    }
}

/**
 * Supervisors that can supervise users.
 */
class Supervisor extends Model
{
}

/**
 * Phone records that profile can contain.
 */
class Phone extends Model
{
}

/**
 * Text note that can be applied for any model.
 */
class Note extends Model
{
}

/**
 * Fake user records repository class. Has debug methods for performing tests.
 */
class EntitiesRepository extends Repository
{
    /**
     * Fake user records repository class. Has debug methods for performing tests.
     *
     * @throws RepositoryException
     */
    public function __construct()
    {
        parent::__construct(User::class);
    }

    /**
     * Base query string. Used for testing SQL string retrieving.
     *
     * @return string
     */
    public function getBaseQueryString(): string
    {
        return $this->query()->toSql();
    }

    /**
     * Helper method for retrieving result string of joined relation query.
     *
     * @param string|array $relations Relation name or array with relations names that should be joined
     *
     * @return string
     * @throws NotImplementedException
     */
    public function getQueryStringWithJoin($relations): string
    {
        $query = $this->query();

        $this->joinRelation($query, $relations);

        return $query->toSql();
    }
}
