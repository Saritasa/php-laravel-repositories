<?php

namespace Saritasa\Repositories\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\SQLiteConnection;
use PDO;
use PHPUnit\Framework\TestCase;
use Saritasa\Exceptions\NotImplementedException;

/**
 * Check join relation repository method.
 */
class JoinRelationTest extends TestCase
{
    /** @var EntitiesRepository */
    private $repository;

    /**
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     *
     * @throws \Saritasa\Exceptions\RepositoryException
     */
    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->repository = new EntitiesRepository();
    }

    /**
     * Test base query retrieving.
     */
    public function testBaseQuery()
    {
        $expectedSql = User::query()->toSql();
        $simpleSql = strtolower($this->repository->getBaseQueryString());

        self::assertEquals($simpleSql, $expectedSql);
    }

    /**
     * Test BelongsTo relation joining.
     */
    public function testBelongsToJoin()
    {
        $actualSql = strtolower($this->repository->getQueryStringWithJoin('role'));
        $expectedSql = User::query()->leftJoin('roles', 'users.role_id', '=', 'roles.id')->toSql();

        self::assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test HasMany relation joining.
     */
    public function testHasManyJoin()
    {
        $actualSql = strtolower($this->repository->getQueryStringWithJoin('cars'));
        $expectedSql = User::query()->leftJoin('cars', 'cars.user_id', '=', 'users.id')->toSql();

        self::assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test HasOne relation joining.
     */
    public function testHasOneJoin()
    {
        $actualSql = strtolower($this->repository->getQueryStringWithJoin('profile'));
        $expectedSql = User::query()->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')->toSql();

        self::assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test HasManyThrough relation joining.
     */
    public function testHasManyThroughJoin()
    {
        $actualSql = strtolower($this->repository->getQueryStringWithJoin('supervisors'));
        $expectedSql = User::query()
            ->leftJoin('supervisors_users', 'supervisors_users.user_id', '=', 'users.id')
            ->leftJoin('supervisors', 'supervisors_users.supervisor_id', '=', 'supervisors.id')
            ->toSql();

        self::assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test joining relations of relations.
     */
    public function testNestedRelationsJoin()
    {
        $actualSql = strtolower($this->repository->getQueryStringWithJoin('profile.phones'));
        $expectedSql = User::query()
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('phones', 'phones.profile_id', '=', 'profiles.id')
            ->toSql();

        self::assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test of multiple relations joining.
     */
    public function testMultipleRelationsJoin()
    {
        $actualSql = strtolower($this->repository->getQueryStringWithJoin(['profile', 'cars']));
        $expectedSql = User::query()
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('cars', 'cars.user_id', '=', 'users.id')
            ->toSql();

        self::assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test that same relation joined only once.
     */
    public function testSingleJoin()
    {
        $actualSql = strtolower($this->repository->getQueryStringWithJoin(['profile.phones', 'profile']));
        $expectedSql = User::query()
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('phones', 'phones.profile_id', '=', 'profiles.id')
            ->toSql();

        self::assertEquals($expectedSql, $actualSql);

        $actualSql = strtolower($this->repository->getQueryStringWithJoin(['profile', 'profile.phones']));
        $expectedSql = User::query()
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('phones', 'phones.profile_id', '=', 'profiles.id')
            ->toSql();

        self::assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test that unsupported relations types are throws valid exception.
     */
    public function testUnsupportedJoin()
    {
        self::expectException(NotImplementedException::class);
        // Try to join polymorphic relation
        $this->repository->getQueryStringWithJoin('notes');
    }

    /**
     * Not declared in model relation method throws exception.
     */
    public function testUnknownJoin()
    {
        self::expectException(RelationNotFoundException::class);
        $this->repository->getQueryStringWithJoin('potato');
    }
}

/**
 * Model class with fake connection generation.
 */
class InMemoryModel extends Model
{
    public function getConnection()
    {
        $pdo = new PDO('sqlite::memory:');

        return new SQLiteConnection($pdo);
    }
}

/**
 * Fake user model class. Used to build test repository and perform tests on this model.
 */
class User extends InMemoryModel
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
class Role extends InMemoryModel
{
}

/**
 * Car that can be owned by user.
 */
class Car extends InMemoryModel
{
}

/**
 * User personal information profile.
 */
class Profile extends InMemoryModel
{
    public function phones(): HasMany
    {
        return $this->hasMany(Phone::class);
    }
}

/**
 * Supervisors that can supervise users.
 */
class Supervisor extends InMemoryModel
{
}

/**
 * Phone records that profile can contain.
 */
class Phone extends InMemoryModel
{
}

/**
 * Text note that can be applied for any model.
 */
class Note extends InMemoryModel
{
}

/**
 * Fake user records repository class. Has debug methods for performing tests.
 */
class EntitiesRepository extends Repository
{
    protected $modelClass = User::class;

    /**
     * Base query string. Used for testing SQL string retrieving.
     *
     * @return string
     */
    public function getBaseQueryString()
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
