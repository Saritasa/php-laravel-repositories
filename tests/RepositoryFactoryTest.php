<?php

namespace Saritasa\LaravelRepositories\Tests;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Saritasa\LaravelRepositories\Contracts\IRepository;
use Saritasa\LaravelRepositories\Exceptions\RepositoryException;
use Saritasa\LaravelRepositories\Exceptions\RepositoryRegisterException;
use Saritasa\LaravelRepositories\Repositories\Repository;
use Saritasa\LaravelRepositories\Repositories\RepositoryFactory;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * Tests work of repository factory.
 */
class RepositoryFactoryTest extends TestCase
{
    /**
     * Connection instance mock.
     *
     * @var Container|MockInterface
     */
    protected $container;

    /**
     * Setup tests setting.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container = Mockery::mock(Container::class);
    }

    /**
     * Test register custom repositories for model with different cases.
     *
     * @dataProvider registerRepositoriesData
     *
     * @param string $model Model for which need to register custom repository
     * @param string $repository Custom repository
     * @param bool $expectException Shows whether should be thrown an exception
     *
     * @return void
     *
     * @throws RepositoryRegisterException
     * @throws RepositoryException
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     */
    public function testRegisterCustomRepositories(string $model, string $repository, bool $expectException): void
    {
        $repositoryFactory = new RepositoryFactory($this->container);

        if ($expectException) {
            $this->expectException(RepositoryRegisterException::class);
        }

        $repositoryFactory->register($model, $repository);

        if (!$expectException) {
            $expectedSRepository = Mockery::mock($repository);
            $this->container->shouldReceive('make')->andReturn($expectedSRepository);
            $actualRepositoryInstance = $repositoryFactory->getRepository($model);
            $this->assertSame($expectedSRepository, $actualRepositoryInstance);
        }
    }

    /**
     * Returns data to test different cases in register method.
     *
     * @return array
     */
    public function registerRepositoriesData(): array
    {
        $modelObject = new class extends Model {
        };

        return [
            ['Not object class', Repository::class, true],
            [Repository::class, Repository::class, true],
            [get_class($modelObject), 'Not existing repository', true],
            [get_class($modelObject), get_class($modelObject), true],
            [get_class($modelObject), Repository::class, false],
        ];
    }

    /**
     * Tests that factory returns the same repository instance each time when it called.
     *
     * @return void
     *
     * @throws RepositoryRegisterException
     * @throws RepositoryException
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     */
    public function testThatEachTimeReturnsTheSameInstance(): void
    {
        $repositoryFactory = new RepositoryFactory($this->container);
        $modelObject = new class extends Model {
        };
        $modelClass = get_class($modelObject);
        $this->container->shouldReceive('make')->andReturn(Mockery::mock(IRepository::class));
        $repositoryFactory->register($modelClass, Repository::class);
        $firstInstance = $repositoryFactory->getRepository($modelClass);
        $secondInstance = $repositoryFactory->getRepository($modelClass);

        $this->assertSame($firstInstance, $secondInstance);
    }
}
