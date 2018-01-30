<?php

namespace Saritasa\Repositories\Base;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Saritasa\DingoApi\Paging\CursorQueryBuilder;
use Saritasa\DingoApi\Paging\CursorRequest;
use Saritasa\DingoApi\Paging\CursorResult;
use Saritasa\DingoApi\Paging\PagingInfo;
use Saritasa\Exceptions\NotImplementedException;
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

    /**
     * Sample instance of model type, handled by this repository
     *
     * @var Model
     */
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
        if (!is_a($this->model, Model::class, true)) {
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

    public function getModelClass(): string
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

    public function getModelValidationRules(Model $model = null): array
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

    public function findOrFail($id): Model
    {
        return $this->query()->findOrFail($id);
    }

    public function findOrNew($id): Model
    {
        return $this->query()->findOrNew($id);
    }

    public function findWhere(array $fieldValues)//: Model
    {
        return $this->query()->where($fieldValues)->first();
    }

    public function create(Model $model): Model
    {
        if (!$model->save()) {
            throw new RepositoryException($this, "Cannot create $this->modelClass record");
        }
        return $model;
    }

    public function save(Model $model): Model
    {
        if (!$model->save()) {
            throw new RepositoryException($this, "Cannot update $this->modelClass record");
        }
        return $model;
    }

    public function delete(Model $model)
    {
        if (!$model->delete()) {
            throw new RepositoryException($this, "Cannot delete $this->modelClass record");
        }
    }

    public function get(): Collection
    {
        return $this->query()->get();
    }

    public function getWhere(array $fieldValues): Collection
    {
        return $this->query()->where($fieldValues)->get();
    }

    public function getPage(PagingInfo $paging, array $fieldValues = null): LengthAwarePaginator
    {
        $query = $this->query()->where($fieldValues);
        return $query->paginate($paging->pageSize, ['*'], 'page', $paging->page);
    }

    public function getCursorPage(CursorRequest $cursor, array $fieldValues = null): CursorResult
    {
        return $this->toCursorResult($cursor, $this->query()->where($fieldValues));
    }

    /**
     * Wrap the query to support cursor pagination with custom sort.
     *
     * @deprecated Now it's default implementation of toCursorResult.
     *
     * @param CursorRequest $cursor Requested cursor parameters
     * @param Builder|QueryBuilder $query Query builder
     * @return CursorResult
     */
    protected function toCursorResultWithCustomSort(CursorRequest $cursor, $query)
    {
        return $this->toCursorResult($cursor, $query);
    }
    
    /**
     * Execute query and return page, corresponding to cursor request
     *
     * @param CursorRequest $cursor Requested cursor parameters
     * @param Builder|QueryBuilder $query Query builder
     * @return CursorResult
     */
    protected function toCursorResult(CursorRequest $cursor, $query): CursorResult
    {
        return (new CursorQueryBuilder($cursor, $query))->getCursor();
    }

    protected function query(): Builder
    {
        return $this->model->query();
    }

    /**
     * Join eager loaded relation and get the related column name.
     *
     * @param Builder|\Illuminate\Database\Query\Builder $query Query builder that should be appended with join clause
     * @param string|array $relations Relation name or array with relations names that should be joined
     *
     * @return Builder|\Illuminate\Database\Query\Builder
     * @throws NotImplementedException
     */
    protected function joinRelation($query, $relations)
    {
        $joinedRelations = is_string($relations) ? [$relations] : $relations;

        foreach ($joinedRelations as $relationToJoin) {
            $nestedQuery = $query;
            foreach (explode('.', $relationToJoin) as $relationName) {
                $model = $nestedQuery->getModel();
                if (!method_exists($model, $relationName)) {
                    throw RelationNotFoundException::make($model, $relationName);
                }

                $relation = $model->$relationName();
                switch (true) {
                    case $relation instanceof BelongsToMany && !$relation instanceof MorphToMany:
                        $pivot = $relation->getTable();
                        $pivotPK = $relation->getExistenceCompareKey();
                        $pivotFK = $relation->getQualifiedParentKeyName();
                        $this->performJoin($query, $pivot, $pivotPK, $pivotFK);

                        $related = $relation->getRelated();
                        $table = $related->getTable();
                        $tablePK = $related->getForeignKey();
                        $foreign = $pivot . '.' . $tablePK;
                        $other = $related->getQualifiedKeyName();

                        $this->performJoin($query, $table, $foreign, $other);
                        break;
                    case $relation instanceof HasOne || $relation instanceof HasMany:
                        $table = $relation->getRelated()->getTable();
                        $foreign = $relation->getQualifiedForeignKeyName();
                        $other = $relation->getQualifiedParentKeyName();
                        break;
                    case $relation instanceof BelongsTo && !$relation instanceof MorphTo:
                        $table = $relation->getRelated()->getTable();
                        $foreign = $relation->getQualifiedForeignKey();
                        $other = $relation->getQualifiedOwnerKeyName();
                        break;
                    default:
                        $requestedRelation = get_class($relation);
                        throw new NotImplementedException("Relation class [{$requestedRelation}] are not supported");
                }

                $query = $this->performJoin($query, $table, $foreign, $other);
                $nestedQuery = $relation->getQuery();
            }
        }

        return $query;
    }

    /**
     * Perform join query.
     *
     * @param Builder|\Illuminate\Database\Query\Builder $query Query builder to apply joins
     * @param string $table Joined table
     * @param string $foreign Foreign key
     * @param string $other Other table key
     *
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    private function performJoin($query, $table, $foreign, $other)
    {
        // Check that table not joined yet
        $joins = [];
        foreach ((array) $query->getQuery()->joins as $key => $join) {
            $joins[] = $join->table;
        }

        if (! in_array($table, $joins)) {
            $query->leftJoin($table, $foreign, '=', $other);
        }

        return $query;
    }
}
