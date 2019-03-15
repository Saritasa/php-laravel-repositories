<?php

namespace Saritasa\LaravelRepositories\Tests;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
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
     */
    public function testRegisterCustomRepositories(string $model, string $repository, bool $expectException): void
    {
        $repositoryFactory = new RepositoryFactory();

        if ($expectException) {
            $this->expectException(RepositoryRegisterException::class);
        }

        $repositoryFactory->register($model, $repository);

        if (!$expectException) {
            $actualRepositoryInstance = $repositoryFactory->getRepository($model);
            $this->assertEquals($repository, get_class($actualRepositoryInstance));
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
     */
    public function testThatEachTimeReturnsTheSameInstance(): void
    {
        $repositoryFactory = new RepositoryFactory();
        $modelObject = new class extends Model {
        };
        $modelClass = get_class($modelObject);
        $repositoryFactory->register($modelClass, Repository::class);
        $firstInstance = $repositoryFactory->getRepository($modelClass);
        $secondInstance = $repositoryFactory->getRepository($modelClass);

        $this->assertSame($firstInstance, $secondInstance);
    }
}
