<?php

namespace Saritasa\LaravelRepositories\Repositories;

use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;
use Saritasa\DingoApi\Paging\CursorRequest;
use Saritasa\DingoApi\Paging\CursorResult;
use Saritasa\DingoApi\Paging\PagingInfo;
use Saritasa\LaravelRepositories\Contracts\IEntity;
use Saritasa\LaravelRepositories\Contracts\IRepository;
use Saritasa\LaravelRepositories\DTO\SortOptions;
use Saritasa\LaravelRepositories\Exceptions\ModelNotFoundException;
use Saritasa\LaravelRepositories\Exceptions\RepositoryException;

/**
 * Wrapper for repositories to store data into cache.
 */
class CachingRepository implements IRepository
{
    /**
     * Wrapped repository, which gets actual data to be cached.
     *
     * @var IRepository
     */
    protected $repository;

    /**
     * Cache storage implementation.
     *
     * @var CacheRepository
     */
    protected $cacheRepository;

    /**
     * Prefix for values, cached by this repository in cache storage.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Cache timeout in minutes.
     *
     * @var int
     */
    protected $cacheTimeout;

    /**
     * Wrapper for repositories to store data into cache.
     *
     * @param IRepository $repository Repositories which call will be cached
     * @param CacheRepository $cacheRepository Cache storage implementation
     * @param string $prefix Cache prefix
     * @param int $cacheTimeout Time in minutes while cache data will be actual
     */
    public function __construct(
        IRepository $repository,
        CacheRepository $cacheRepository,
        string $prefix,
        int $cacheTimeout = 10
    ) {
        $this->repository = $repository;
        $this->prefix = $prefix;
        $this->cacheTimeout = $cacheTimeout;
        $this->cacheRepository = $cacheRepository;
    }

    /**
     * Get data from cache.
     * If it not exists in cache got it in repository and store in cache.
     *
     * @param string $key Key to find data in cache
     * @param Closure $dataGetter Function to get data in original repository
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    protected function cached(string $key, Closure $dataGetter)
    {
        if ($this->cacheRepository->has($key)) {
            return $this->cacheRepository->get($key);
        }
        $result = $dataGetter();
        $this->cacheRepository->put($key, $result, $this->cacheTimeout);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function findOrFail($id): IEntity
    {
        $result = $this->cachedFind($id);
        if (!$result) {
            throw new ModelNotFoundException($this, "{$this->repository->getEntityClass()} with ID=$id was not found");
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function findWhere(array $fieldValues, ?SortOptions $sortOptions = null): ?IEntity
    {
        $key = $this->prefix . ":find:" . md5(serialize($fieldValues). serialize($sortOptions));
        return $this->cached($key, function () use ($fieldValues, $sortOptions) {
            return $this->repository->findWhere($fieldValues, $sortOptions);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function create(IEntity $model): IEntity
    {
        return $this->repository->create($model);
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function save(IEntity $model): IEntity
    {
        $result = $this->repository->save($model);
        $this->invalidate($model);
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function delete(IEntity $model): void
    {
        $this->repository->delete($model);
        $this->invalidate($model);
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function get(array $fieldValues = [], ?SortOptions $sortOptions = null): Collection
    {
        return $this->cached("$this->prefix:all", function () use ($fieldValues, $sortOptions) {
            return $this->repository->get($fieldValues, $sortOptions);
        });
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function getPage(
        PagingInfo $paging,
        array $fieldValues = [],
        ?SortOptions $sortOptions = null
    ): LengthAwarePaginator {
        $key = $this->prefix . ":page:" . md5(serialize($paging->toArray()) . serialize($fieldValues));
        return $this->cached($key, function () use ($paging, $fieldValues) {
            return $this->repository->getPage($paging, $fieldValues);
        });
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function getCursorPage(
        CursorRequest $cursor,
        array $fieldValues = [],
        ?SortOptions $sortOptions = null
    ): CursorResult {
        $key = $this->prefix . ":page:" . md5(serialize($cursor->toArray()) . serialize($fieldValues));
        return $this->cached($key, function () use ($cursor, $fieldValues) {
            return $this->repository->getCursorPage($cursor, $fieldValues);
        });
    }

    /**
     * Find model in original repository.
     *
     * @param string|int id Id to find
     *
     * @return IEntity|null
     *
     * @throws RepositoryException
     */
    private function find($id): ?IEntity
    {
        try {
            return $this->repository->findOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return null;
        }
    }

    /**
     * Find model in cache by id.
     *
     * @param string|int $id Id to find
     *
     * @return IEntity|null
     *
     * @throws InvalidArgumentException
     */
    protected function cachedFind($id): ?IEntity
    {
        return $this->cached("$this->prefix:$id", function () use ($id) {
            return $this->find($id);
        });
    }

    /**
     * Invalidate model in cache.
     *
     * @param IEntity $model Entity to invalidate
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    protected function invalidate(IEntity $model): void
    {
        $key = $this->prefix . ":" . $model->getKey();
        if ($this->cacheRepository->has($key)) {
            $this->cacheRepository->forget($key);
        }
        $this->cacheRepository->forget("$this->prefix.:all");
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass(): string
    {
        return $this->repository->getEntityClass();
    }

    /**
     * Proxy for get methods.
     *
     * @param string $name Method name
     * @param array $arguments Method arguments
     *
     * @return mixed
     *
     * @throws RepositoryException
     * @throws InvalidArgumentException
     */
    public function __call(string $name, array $arguments)
    {
        if (0 === strpos($name, 'get')) {
            $argHash = md5(serialize($arguments));
            $key = "$this->prefix:$name:$argHash";
            return $this->cached($key, function () use ($name, $arguments) {
                return call_user_func_array([$this->repository, $name], $arguments);
            });
        }
        throw new RepositoryException($this, "Caching repository proxies only get* methods");
    }

    /**
     * Proxy for repository params.
     *
     * @param string $name Parameter name
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public function __get(string $name)
    {
        return $this->cached("$this->prefix:$name", function () use ($name) {
            return $this->repository->$name;
        });
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function count(array $fieldValues = []): int
    {
        $key = $this->prefix . ":all:count";
        return $this->cached($key, function () use ($fieldValues) {
            return $this->repository->count($fieldValues);
        });
    }
}
