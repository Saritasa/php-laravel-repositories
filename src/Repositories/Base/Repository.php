<?php

namespace Saritasa\Repositories\Base;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Saritasa\DingoApi\Paging\CursorRequest;
use Saritasa\DingoApi\Paging\CursorResult;
use Saritasa\DingoApi\Paging\CursorResultAuto;
use Saritasa\DingoApi\Paging\PagingInfo;
use Saritasa\Exceptions\RepositoryException;

/**
 * Superclass for any repository.
 * Contains logic of receipt the entity list, with filters (search) and sort.
 */
class Repository implements IRepository
{
    /**
     * FQN model name of the repository. Must be determined in the inheritors.
     *
     * Note: PHP 7 allows to use "SomeModel::class" just as property initial value. Use it.
     *
     * @var string
     */
    protected $modelClass;

    /**
     * List of fields, allowed to use in the search
     *
     * Should be determine in the inheritors. Determines the result of the list request of entities.
     *
     * @var array
     */
    protected $searchableFields = [];

    /** @var Model */
    private $model;

    public function __construct()
    {
        if (!$this->modelClass) {
            throw new RepositoryException($this, 'Mandatory property $modelClass not defined');
        }
        try {
            $this->model = new $this->modelClass;
        } catch (\Exception $e) {
            throw new RepositoryException($this, "Error creating instance of model $this->modelClass", 500, $e);
        }
        if (!is_a($this->model, Model::class, true))
        {
            throw new RepositoryException($this, "$this->modelClass must extend ".Model::class);
        }
    }

    public function __get($key)
    {
        $result = null;
        switch ($key) {
            case 'model':
                $result = new $this->modelClass;
                break;
            case 'searchableFields':
                $result = $this->searchableFields;
                break;
            case 'modelValidationRules':
                $result = $this->getModelValidationRules();
                break;
            default:
                throw new RepositoryException($this, "Unknown property ! $key requested");
        }
        return $result;
    }

    function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Get visible fields from model
     */
    public function getVisibleFields()
    {
        return $this->model->getVisible();
    }

    function getModelValidationRules(Model $model = null): array
    {
        $model = $model ?: new $this->modelClass;
        if (method_exists($model, 'getValidationRules')) {
            return $model->getValidationRules();
        }
        if (isset($this->validationRules)) {
            return $this->validationRules;
        }
        return [];
    }

    function findOrFail($id): Model
    {
        return $this->query()->findOrFail($id);
    }

    function findOrNew($id): Model
    {
        return $this->query()->findOrNew($id);
    }

    function findWhere(array $fieldValues)//: Model
    {
        return $this->query()->where($fieldValues)->first();
    }

    function create(Model $model): Model
    {
        if (!$model->save()) {
            throw new RepositoryException($this, "Cannot create $this->modelClass record");
        }
        return $model;
    }

    function save(Model $model): Model
    {
        if (!$model->save()) {
            throw new RepositoryException($this, "Cannot create $this->modelClass record");
        }
        return $model;
    }

    function delete(Model $model)
    {
        if (!$model->delete()) {
            throw new RepositoryException($this, "Cannot delete $this->modelClass record");
        }
    }

    function get(): Collection
    {
        return $this->query()->get();
    }

    function getWhere(array $fieldValues): Collection
    {
        return $this->query()->where($fieldValues)->get();
    }

    function getPage(PagingInfo $paging, array $fieldValues = null): LengthAwarePaginator
    {
        $query = $this->query()->where($fieldValues);
        return $query->paginate($paging->pageSize, ['*'], 'page', $paging->page);
    }

    function getCursorPage(CursorRequest $cursor, array $fieldValues = null): CursorResult
    {
        return $this->toCursorResult($cursor, $this->query()->where($fieldValues));
    }

    /**
     * Wrap the query to support cursor pagination with custom sort.
     * @param CursorRequest $cursor Requested cursor parameters
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $originalQuery
     * @return CursorResult
     */
    protected function toCursorResultWithCustomSort(CursorRequest $cursor, $originalQuery)
    {
        $originalQuery->crossJoin(DB::raw('(SELECT @row := 1) as r'));
        $idKey = CursorResultAuto::ROW_NUM_COLUMN;
        $wrappedQuery = DB::table(
            DB::raw("(SELECT *, (@row := @row+1) as {$idKey} FROM (" . $originalQuery->toSql() . ") as t1) as t2")
        )->mergeBindings(($originalQuery instanceof Builder) ? $originalQuery : $originalQuery->getQuery());

        /** @var Model $fakeModel */
        $originalClass = get_class($this->model);
        $fakeModel = new $originalClass();
        $fakeModel->setKeyName($idKey);
        $fakeModel->setTable(DB::raw("(" . $wrappedQuery->toSql() . ") AS " . $this->model->getTable()));
        $items = $fakeModel->newQuery()
            ->mergeBindings($wrappedQuery)
            ->where($idKey, '>', $cursor->current)
            ->take($cursor->pageSize)
            ->get();

        return new CursorResultAuto($cursor, $items);
    }
    
    /**
     * @param CursorRequest $cursor Requested cursor parameters
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @return CursorResult
     */
    protected function toCursorResult(CursorRequest $cursor, $query): CursorResult
    {
        $idKey = $this->model->getQualifiedKeyName();
        $items = $query->where($idKey, '>', $cursor->current)->take($cursor->pageSize)->get();

        return new CursorResultAuto($cursor, $items);
    }

    private function query(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->model->query();
    }
}
