<?php

namespace Saritasa\LaravelRepositories\Repositories;

use Carbon\Carbon;
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
use Saritasa\LaravelRepositories\DTO\Criterion;
use Saritasa\LaravelRepositories\DTO\SortOptions;
use Saritasa\LaravelRepositories\Exceptions\BadCriteriaException;
use Saritasa\LaravelRepositories\Exceptions\ModelNotFoundException;
use Saritasa\Exceptions\NotImplementedException;
use Saritasa\LaravelRepositories\Exceptions\RepositoryException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Saritasa\LaravelRepositories\Contracts\IRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException as EloquentModelNotFountException;
use Throwable;

/**
 * Eloquent model repository. Manages stored entities.
 */
class Repository implements IRepository
{
    /**
     * FQN model name of the repository. Must be determined in the inheritors.
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
     * Available operators for operations with single value.
     *
     * @var array
     */
    protected $singleOperators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    /**
     * Available operators for operations with multiple values.
     *
     * @var array
     */
    protected $multipleOperators = ['in', 'not in'];

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
     * @param string $modelClass Model class for this repository
     *
     * @throws RepositoryException
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
        try {
            $this->model = new $this->modelClass;
        } catch (Throwable $e) {
            throw new RepositoryException($this, "Error creating instance of model $this->modelClass", 500, $e);
        }
        if (!$this->model instanceof Model) {
            throw new RepositoryException($this, "$this->modelClass must extend " . Model::class, 500);
        }
    }

    /** {@inheritdoc} */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
    public function findOrFail($id): Model
    {
        try {
            return $this->query()->findOrFail($id);
        } catch (EloquentModelNotFountException $exception) {
            throw new ModelNotFoundException($this, $id, $exception);
        }
    }

    /** {@inheritdoc} */
    public function create(Model $model): Model
    {
        if (!$model->save()) {
            throw new RepositoryException($this, "Cannot create $this->modelClass record");
        }
        return $model;
    }

    /** {@inheritdoc} */
    public function save(Model $model): Model
    {
        if (!$model->save()) {
            throw new RepositoryException($this, "Cannot update $this->modelClass record");
        }
        return $model;
    }

    /** {@inheritdoc} */
    public function delete(Model $model): void
    {
        try {
            $result = $model->delete();
        } catch (Throwable $exception) {
            throw new RepositoryException($this, "Cannot delete $this->modelClass record", 500, $exception);
        }
        if (!$result) {
            throw new RepositoryException($this, "Cannot delete $this->modelClass record");
        }
    }

    /** {@inheritdoc} */
    public function get(): Collection
    {
        return $this->query()->get();
    }

    /** {@inheritdoc} */
    public function getPage(PagingInfo $paging, array $fieldValues = []): LengthAwarePaginator
    {
        $query = $this->query()->where($fieldValues);
        return $query->paginate($paging->pageSize, ['*'], 'page', $paging->page);
    }

    /** {@inheritdoc} */
    public function getCursorPage(CursorRequest $cursor, array $fieldValues = []): CursorResult
    {
        return $this->toCursorResult($cursor, $this->query()->where($fieldValues));
    }

    /**
     * Execute query and return page, corresponding to cursor request
     *
     * @param CursorRequest $cursor Requested cursor parameters
     * @param Builder|QueryBuilder $query Query builder
     *
     * @return CursorResult
     */
    protected function toCursorResult(CursorRequest $cursor, $query): CursorResult
    {
        return (new CursorQueryBuilder($cursor, $query))->getCursor();
    }

    /**
     * Returns base query.
     *
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
        foreach ((array)$query->getQuery()->joins as $key => $join) {
            $joins[] = $join->table;
        }

        if (!in_array($table, $joins)) {
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

    /** {@inheritdoc} */
    public function count(): int
    {
        return $this->query()->count();
    }

    /** {@inheritdoc} */
    public function getSearchableFields(): array
    {
        return $this->searchableFields;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RepositoryException
     */
    public function getWith(
        array $with,
        ?array $withCounts = null,
        ?array $where = null,
        ?SortOptions $sortOptions = null
    ): Collection {
        $builder = $this->getWithBuilder($with, $withCounts, null, $sortOptions);

        if ($where) {
            $builder->addNestedWhereQuery($this->getNestedWhereConditions($builder->getQuery(), $where));
        }

        return $builder->get();
    }

    /**
     * {@inheritdoc}
     *
     * @throws RepositoryException
     */
    public function getWhere(array $fieldValues): Collection
    {
        $builder = $this->query();

        return $builder
            ->addNestedWhereQuery($this->getNestedWhereConditions($builder->getQuery(), $fieldValues))
            ->get();
    }

    /**
     * {@inheritdoc}
     *
     * @throws RepositoryException
     */
    public function findWhere(array $fieldValues): ?Model
    {
        $builder = $this->query();

        return $builder
            ->addNestedWhereQuery($this->getNestedWhereConditions($builder->getQuery(), $fieldValues))
            ->first();
    }

    /**
     * Returns query builder with applied criteria. This method work recursively and group nested criteria in one level.
     *
     * @param QueryBuilder $builder Top level query builder
     * @param array $criteria Nested list of criteria
     *
     * @return QueryBuilder
     *
     * @throws BadCriteriaException when any criterion is not valid
     */
    protected function getNestedWhereConditions(QueryBuilder $builder, array $criteria): QueryBuilder
    {
        $subQuery = $builder->forNestedWhere();
        foreach ($criteria as $key => $criterionData) {
            switch (true) {
                case is_string($key)
                    && ((!is_array($criterionData) && !is_object($criterionData))
                        || $criterionData instanceof Carbon):
                    $criterion = new Criterion([Criterion::ATTRIBUTE => $key, Criterion::VALUE => $criterionData]);
                    break;
                case $criterionData instanceof Criterion:
                    $criterion = $criterionData;
                    break;
                case is_int($key) && is_array($criterionData) && !empty($criterionData):
                    $criterion = $this->parseCriterion($criterionData);
                    break;
                default:
                    throw new BadCriteriaException($this);
            }

            if (!$this->isCriterionValid($criterion)) {
                $subQuery->addNestedWhereQuery($this->getNestedWhereConditions($subQuery, $criterionData));
                continue;
            }

            switch ($criterion->operator) {
                case 'in':
                    $subQuery->whereIn($criterion->attribute, $criterion->value, $criterion->boolean);
                    break;
                case 'not in':
                    $subQuery->whereNotIn($criterion->attribute, $criterion->value, $criterion->boolean);
                    break;
                default:
                    $subQuery->where(
                        $criterion->attribute,
                        $criterion->operator,
                        $criterion->value,
                        $criterion->boolean
                    );
                    break;
            }
        }

        return $subQuery;
    }

    /**
     * Transforms criterion data into DTO.
     *
     * @param array $criterionData Criterion data to transform
     *
     * @return Criterion
     */
    protected function parseCriterion(array $criterionData): Criterion
    {
        return new Criterion([
            Criterion::ATTRIBUTE => $criterionData[0] ?? null,
            Criterion::OPERATOR => $criterionData[1] ?? null,
            Criterion::VALUE => $criterionData[2] ?? null,
            Criterion::BOOLEAN => $criterionData[3] ?? 'and',
        ]);
    }

    /**
     * Checks whether the criterion is valid.
     *
     * @param Criterion $criterion Criterion to check validity
     *
     * @return boolean
     */
    protected function isCriterionValid(Criterion $criterion): bool
    {
        $isMultipleOperator = (is_array($criterion->value) || $criterion->value instanceof Collection) &&
            in_array($criterion->operator, $this->multipleOperators);
        $isSingleOperator = !is_array($criterion->value) && in_array($criterion->operator, $this->singleOperators);

        return is_string($criterion->attribute) &&
            is_string($criterion->boolean) &&
            ($isMultipleOperator || $isSingleOperator);
    }
}
