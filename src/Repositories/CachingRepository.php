<?php

namespace Saritasa\Repositories;

use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Saritasa\DingoApi\Paging\CursorRequest;
use Saritasa\DingoApi\Paging\CursorResult;
use Saritasa\DingoApi\Paging\PagingInfo;
use Saritasa\DTO\SortOptions;
use Saritasa\Exceptions\ModelNotFoundException;
use Saritasa\Exceptions\RepositoryException;
use Saritasa\Contracts\IRepository;

/**
 *
 */
class CachingRepository implements IRepository
{
    /**
     * Wrapped repository, which gets actual data to be cached.
     *
     * @var IRepository
     */
    private $repo;

    /**
     * Cache repository.
     *
     * @var CacheRepository
     */
    protected $cacheRepository;

    /**
     * Prefix for values, cached by this repository in cache storage.
     *
     * @var string
     */
    private $prefix;

    /**
     * Cache timeout
     *
     * @var int
     */
    private $cacheTimeout;

    /* @var string */
    private $modelClass;

    public function __construct(
        IRepository $repository,
        CacheRepository $cacheRepository,
        string $prefix,
        int $cacheTimeout = 10
    ) {
        $this->repo = $repository;
        $this->prefix = $prefix;
        $this->cacheTimeout = $cacheTimeout;
        $this->modelClass = $repository->getModelClass();
    }

    private function cached(string $key, Closure $dataGetter)
    {
        if ($this->cacheRepository->has($key)) {
            return $this->cacheRepository->get($key);
        }
        $result = $dataGetter();
        $this->cacheRepository->put($key, $result, $this->cacheTimeout);
        return $result;
    }

    public function getModelValidationRules(): array
    {
        return $this->repo->getModelValidationRules();
    }

    public function findOrFail($id): Model
    {
        $result = $this->cachedFind($id);
        if (!$result) {
            throw new ModelNotFoundException($this, "$this->modelClass with ID=$id was not found");
        }
        return $result;
    }

    public function findWhere(array $fieldValues): ?Model
    {
        $key = $this->prefix . ":find:" . md5(serialize($fieldValues));
        return $this->cached($key, function () use ($fieldValues) {
            return $this->repo->findWhere($fieldValues);
        });
    }

    public function create(Model $model): Model
    {
        return $this->repo->create($model);
    }

    public function save(Model $model): Model
    {
        $result = $this->repo->save($model);
        $this->invalidate($model);
        return $result;
    }

    public function delete(Model $model): void
    {
        $this->repo->delete($model);
        $this->invalidate($model);
    }

    public function get(): Collection
    {
        return $this->cached("$this->prefix:all", function () {
            return $this->repo->get();
        });
    }

    public function getWhere(array $fieldValues): Collection
    {
        $key = $this->prefix . ":get:" . md5(serialize($fieldValues));
        return $this->cached($key, function () use ($fieldValues) {
            return $this->repo->getWhere($fieldValues);
        });
    }

    public function getPage(PagingInfo $paging, array $fieldValues = null): LengthAwarePaginator
    {
        $key = $this->prefix . ":page:" . md5(serialize($paging->toArray()) . serialize($fieldValues));
        return $this->cached($key, function () use ($paging, $fieldValues) {
            return $this->repo->getPage($paging, $fieldValues);
        });
    }

    public function getCursorPage(CursorRequest $cursor, array $fieldValues = null): CursorResult
    {
        $key = $this->prefix . ":page:" . md5(serialize($cursor->toArray()) . serialize($fieldValues));
        return $this->cached($key, function () use ($cursor, $fieldValues) {
            return $this->repo->getCursorPage($cursor, $fieldValues);
        });
    }

    private function find($id): ?Model
    {
        try {
            return $this->repo->findOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return null;
        }
    }

    private function cachedFind($id) //: Model
    {
        return $this->cached("$this->prefix:$id", function () use ($id) {
            return $this->find($id);
        });
    }

    private function invalidate(Model $model)
    {
        $key = $this->prefix . ":" . $model->getKey();
        if ($this->cacheRepository->has($key)) {
            $this->cacheRepository->forget($key);
        }
        $this->cacheRepository->forget("$this->prefix.:all");
    }

    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    public function __call($name, $arguments)
    {
        if (0 === strpos($name, 'get')) {
            $argHash = md5(serialize($arguments));
            $key = "$this->prefix:$name:$argHash";
            return $this->cached($key, function () use ($name, $arguments) {
                return call_user_func_array([$this->repo, $name], $arguments);
            });
        }
        throw new RepositoryException($this, "Caching repository proxies only get* methods");
    }

    public function __get($name)
    {
        return $this->cached("$this->prefix:$name", function () use ($name) {
            return $this->repo->$name;
        });
    }

    public function getWith(
        array $with,
        array $withCounts = null,
        array $where = null,
        SortOptions $sortOptions = null
    ): Collection {
        // TODO: Implement getWith() method.
    }
}
