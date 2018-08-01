<?php

namespace Saritasa\LaravelRepositories\Repositories;

use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Saritasa\DingoApi\Paging\CursorRequest;
use Saritasa\DingoApi\Paging\CursorResult;
use Saritasa\DingoApi\Paging\PagingInfo;
use Saritasa\LaravelRepositories\DTO\SortOptions;
use Saritasa\LaravelRepositories\Exceptions\ModelNotFoundException;
use Saritasa\LaravelRepositories\Exceptions\RepositoryException;
use Saritasa\LaravelRepositories\Contracts\IRepository;

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
     */
    public function getModelValidationRules(): array
    {
        return $this->repository->getModelValidationRules();
    }

    /**
     * {@inheritdoc}
     */
    public function findOrFail($id): Model
    {
        $result = $this->cachedFind($id);
        if (!$result) {
            throw new ModelNotFoundException($this, "{$this->repository->getModelClass()} with ID=$id was not found");
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function findWhere(array $fieldValues): ?Model
    {
        $key = $this->prefix . ":find:" . md5(serialize($fieldValues));
        return $this->cached($key, function () use ($fieldValues) {
            return $this->repository->findWhere($fieldValues);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function create(Model $model): Model
    {
        return $this->repository->create($model);
    }

    /**
     * {@inheritdoc}
     */
    public function save(Model $model): Model
    {
        $result = $this->repository->save($model);
        $this->invalidate($model);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Model $model): void
    {
        $this->repository->delete($model);
        $this->invalidate($model);
    }

    /**
     * {@inheritdoc}
     */
    public function get(): Collection
    {
        return $this->cached("$this->prefix:all", function () {
            return $this->repository->get();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getWhere(array $fieldValues): Collection
    {
        $key = $this->prefix . ":get:" . md5(serialize($fieldValues));
        return $this->cached($key, function () use ($fieldValues) {
            return $this->repository->getWhere($fieldValues);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getPage(PagingInfo $paging, array $fieldValues = []): LengthAwarePaginator
    {
        $key = $this->prefix . ":page:" . md5(serialize($paging->toArray()) . serialize($fieldValues));
        return $this->cached($key, function () use ($paging, $fieldValues) {
            return $this->repository->getPage($paging, $fieldValues);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getCursorPage(CursorRequest $cursor, array $fieldValues = []): CursorResult
    {
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
     * @return Model|null
     */
    private function find($id): ?Model
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
     * @return Model|null
     */
    protected function cachedFind($id): ?Model
    {
        return $this->cached("$this->prefix:$id", function () use ($id) {
            return $this->find($id);
        });
    }

    /**
     * Invalidate model in cache.
     *
     * @param Model $model Model to invalidate
     *
     * @return void
     */
    protected function invalidate(Model $model): void
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
    public function getModelClass(): string
    {
        return $this->repository->getModelClass();
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
     */
    public function __get(string $name)
    {
        return $this->cached("$this->prefix:$name", function () use ($name) {
            return $this->repository->$name;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getWith(
        array $with,
        array $withCounts = [],
        array $where = [],
        ?SortOptions $sortOptions = null
    ): Collection {
        $key = $this->prefix . ":get:" . md5(serialize($with) . serialize($withCounts) . serialize($where));
        return $this->cached($key, function () use ($with, $withCounts, $where, $sortOptions) {
            return $this->repository->getWith($with, $withCounts, $where, $sortOptions);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        $key = $this->prefix . ":all:count";
        return $this->cached($key, function () {
            return $this->repository->count();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchableFields(): array
    {
       return $this->repository->getSearchableFields();
    }
}
