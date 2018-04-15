<?php

namespace Saritasa\Repositories;

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
use Illuminate\Support\Collection;
use Saritasa\DingoApi\Paging\CursorQueryBuilder;
use Saritasa\DingoApi\Paging\CursorRequest;
use Saritasa\DingoApi\Paging\CursorResult;
use Saritasa\DingoApi\Paging\PagingInfo;
use Saritasa\DTO\SortOptions;
use Saritasa\Exceptions\ModelNotFoundException;
use Saritasa\Exceptions\NotImplementedException;
use Saritasa\Exceptions\RepositoryException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Saritasa\Contracts\IRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException as EloquentModelNotFountException;

/**
 * Eloquent model repository. Manages stored entities.
 * In addition to base repository allows to retrieve list of entities by passed rules with requested related data.
 */
class EloquentRepository implements IRepository
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

    /**
     * Superclass for any repository.
     * Contains logic of receipt the entity list, with filters (search) and sort.
     *
     * @param string $modelClass
     * @throws RepositoryException
     */
    public function __construct(string $modelClass)
    {
       $this->modelClass = $modelClass;
        try {
            $this->model = new $this->modelClass;
        } catch (\Throwable $e) {
            throw new RepositoryException($this, "Error creating instance of model $this->modelClass", 500, $e);
        }
    }

    /**
     * Returns model class of current repository.
     *
     * @return string
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * @return array
     */
    public function getVisibleFields()
    {
        return $this->model->getVisible();
    }

    /**
     * Returns model validation rules.
     *
     * @return array
     */
    public function getModelValidationRules(): array
    {
        if (method_exists($this->model, 'getValidationRules')) {
            return $this->model->getValidationRules();
        }
        if (isset($this->validationRules)) {
            return $this->validationRules;
        }
        return [];
    }

    /**
     * Find model by their id.
     *
     * @param string|int $id
     *
     * @return Model
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail($id): Model
    {
        try {
            $model = $this->query()->findOrFail($id);
            return $model;
        } catch (EloquentModelNotFountException $exception) {
            throw new ModelNotFoundException($this, [$id], $exception);
        }
    }

    /**
     * Returns first model matching given filters.
     *
     * @param array $fieldValues Filters collection
     * @return Model|null
     */
    public function findWhere(array $fieldValues): ?Model
    {
        return $this->query()->where($fieldValues)->first();
    }

    /**
     * Create modal in storage.
     *
     * @param Model $model Model to create
     * @return Model
     *
     * @throws RepositoryException
     */
    public function create(Model $model): Model
    {
        if (!$model->save()) {
            throw new RepositoryException($this, "Cannot create $this->modelClass record");
        }
        return $model;
    }

    /**
     * Save model in storage.
     *
     * @param Model $model Model to save
     *
     * @return Model
     *
     * @throws RepositoryException
     */
    public function save(Model $model): Model
    {
        if (!$model->save()) {
            throw new RepositoryException($this, "Cannot update $this->modelClass record");
        }
        return $model;
    }

    /**
     * Delete model in storage.
     *
     * @param Model $model Model to delete
     * @return void
     *
     * @throws RepositoryException
     */
    public function delete(Model $model): void
    {
        if (!$model->delete()) {
            throw new RepositoryException($this, "Cannot delete $this->modelClass record");
        }
    }

    /**
     * Returns models list.
     *
     * @return Collection
     */
    public function get(): Collection
    {
        return $this->query()->get();
    }

    /**
     * Returns models list matching given filters.
     *
     * @param array $fieldValues Filters collection
     *
     * @return Collection
     */
    public function getWhere(array $fieldValues): Collection
    {
        return $this->query()->where($fieldValues)->get();
    }

    /**
     * Get models collection as pagination.
     *
     * @param PagingInfo $paging Paging information
     * @param array|null $fieldValues Filters collection
     *
     * @return LengthAwarePaginator
     */
    public function getPage(PagingInfo $paging, array $fieldValues = null): LengthAwarePaginator
    {
        $query = $this->query()->where($fieldValues);
        return $query->paginate($paging->pageSize, ['*'], 'page', $paging->page);
    }

    /**
     * Get models collection as cursor.
     *
     * @param CursorRequest $cursor
     * @param array|null $fieldValues Filters collection
     *
     * @return CursorResult
     */
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
     * @param Builder $query Query builder
     * @return CursorResult
     */
    protected function toCursorResultWithCustomSort(CursorRequest $cursor, $query): CursorResult
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

    /**
     * @return Builder
     */
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

    /**
     * Returns builder that satisfied requested conditions, with eager loaded requested relations and relations counts,
     * ordered by requested rules.
     *
     * @param array $with Which relations should be preloaded
     * @param array $withCounts Which related entities should be counted
     * @param array $where Conditions that retrieved entities should satisfy
     * @param SortOptions $sortOptions How list of item should be sorted
     *
     * @return Builder
     */
    protected function getWithBuilder(
        array $with,
        array $withCounts = null,
        array $where = null,
        SortOptions $sortOptions = null
    ): Builder {
        return $this->query()
            ->when($with, function (Builder $query) use ($with) {
                return $query->with($with);
            })
            ->when($withCounts, function (Builder $query) use ($withCounts) {
                return $query->withCount($withCounts);
            })
            ->when($where, function (Builder $query) use ($where) {
                return $query->where($where);
            })
            ->when($sortOptions, function (Builder $query) use ($sortOptions) {
                return $query->orderBy($sortOptions->orderBy, $sortOptions->sortOrder);
            });
    }

    /**
     * Retrieve list of entities that satisfied $where conditions.
     *
     * @param array $with Which relations should be preloaded
     * @param array $withCounts Which related entities should be counted
     * @param array $where Conditions that retrieved entities should satisfy
     * @param SortOptions $sortOptions How list of item should be sorted
     *
     * @return Collection
     */
    public function getWith(
        array $with,
        array $withCounts = null,
        array $where = null,
        SortOptions $sortOptions = null
    ): Collection {
        return $this->getWithBuilder($with, $withCounts, $where, $sortOptions)->get();
    }
}
