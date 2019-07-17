<?php

namespace Saritasa\LaravelRepositories\Repositories\Adapters;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException as EloquentModelNotFountException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Saritasa\DingoApi\Paging\CursorQueryBuilder;
use Saritasa\DingoApi\Paging\CursorRequest;
use Saritasa\DingoApi\Paging\CursorResult;
use Saritasa\DingoApi\Paging\PagingInfo;
use Saritasa\LaravelRepositories\Contracts\IEntity;
use Saritasa\LaravelRepositories\Contracts\IRepository;
use Saritasa\LaravelRepositories\DTO\Criterion;
use Saritasa\LaravelRepositories\DTO\RelationCriterion;
use Saritasa\LaravelRepositories\DTO\SortOptions;
use Saritasa\LaravelRepositories\Entities\EloquentEntity;
use Saritasa\LaravelRepositories\Exceptions\BadCriteriaException;
use Saritasa\LaravelRepositories\Exceptions\ModelNotFoundException;
use Saritasa\LaravelRepositories\Exceptions\RepositoryException;
use Throwable;

/**
 * Eloquent model repository. Manages stored entities.
 */
class EloquentAdapterRepository implements IRepository
{
    /**
     * FQN model name of the repository. Must be determined in the inheritors.
     *
     * @var string
     */
    protected $modelClass;

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
     * @var EloquentEntity
     */
    private $model;

    /**
     * Validation rules for served by this repository entity.
     *
     * @var array
     */
    protected $validationRules = [];

    /**
     * Superclass for any repository.
     * Contains logic of receipt the entity list, with filters (search) and sort.
     *
     * @param string $modelClass Entity class for this repository
     *
     * @throws RepositoryException
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
        try {
            $this->model = new $this->modelClass();
        } catch (Throwable $e) {
            throw new RepositoryException($this, "Error creating instance of model $this->modelClass", 500, $e);
        }
        if (!$this->model instanceof EloquentEntity) {
            throw new RepositoryException($this, "$this->modelClass must extend " . EloquentEntity::class);
        }
    }

    /** {@inheritdoc} */
    public function getEntityClass(): string
    {
        return $this->modelClass;
    }

    /** {@inheritdoc} */
    public function findOrFail($id): IEntity
    {
        if (!is_numeric($id) && empty($id)) {
            throw new RepositoryException($this, 'Provided id can not be empty.');
        }

        if (($this->model->getKeyType() === 'int' && !is_int($id)) ||
            ($this->model->getKeyType() === 'string' && is_int($id))
        ) {
            throw new RepositoryException($this, 'Provided id type does not match model primary key type.');
        }

        try {
            /**
             * Found entity to return.
             *
             * @var EloquentEntity $entity
             */
            $entity = $this->query()->findOrFail($id);

            return $entity;
        } catch (EloquentModelNotFountException $exception) {
            throw new ModelNotFoundException($this, $id, $exception);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param EloquentEntity $model Eloquent entity to save
     */
    public function create(IEntity $model): IEntity
    {
        $this->validateServedEntity($model);

        if (!$model->save()) {
            throw new RepositoryException($this, "Cannot create $this->modelClass record");
        }

        return $model;
    }

    /**
     * {@inheritdoc}
     *
     * @param EloquentEntity $model Eloquent entity to save
     */
    public function save(IEntity $model): IEntity
    {
        $this->validateServedEntity($model);

        if (!$model->save()) {
            throw new RepositoryException($this, "Cannot update $this->modelClass record");
        }

        return $model;
    }

    /**
     * {@inheritdoc}
     *
     * @param EloquentEntity $model Eloquent entity to delete
     */
    public function delete(IEntity $model): void
    {
        $this->validateServedEntity($model);

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
    public function get(array $fieldValues = [], ?SortOptions $sortOptions = null): Collection
    {
        $builder = $this->query()
            ->when($sortOptions, function (Builder $query) use ($sortOptions) {
                return $query->orderBy($sortOptions->orderBy, $sortOptions->sortOrder);
            });

        return $builder
            ->addNestedWhereQuery($this->getNestedWhereConditions($builder->getQuery(), $fieldValues))
            ->get();
    }

    /** {@inheritdoc} */
    public function getPage(
        PagingInfo $paging,
        array $fieldValues = [],
        ?SortOptions $sortOptions = null
    ): LengthAwarePaginator {
        $builder = $this
            ->query()
            ->when($sortOptions, function (Builder $query) use ($sortOptions) {
                return $query->orderBy($sortOptions->orderBy, $sortOptions->sortOrder);
            });
        $builder->addNestedWhereQuery($this->getNestedWhereConditions($builder->getQuery(), $fieldValues));

        return $builder->paginate($paging->pageSize, ['*'], 'page', $paging->page);
    }

    /** {@inheritdoc} */
    public function getCursorPage(
        CursorRequest $cursor,
        array $fieldValues = [],
        ?SortOptions $sortOptions = null
    ): CursorResult {
        $builder = $this
            ->query()
            ->when($sortOptions, function (Builder $query) use ($sortOptions) {
                return $query->orderBy($sortOptions->orderBy, $sortOptions->sortOrder);
            });
        $builder->addNestedWhereQuery($this->getNestedWhereConditions($builder->getQuery(), $fieldValues));

        return $this->toCursorResult($cursor, $builder);
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

    /** {@inheritdoc}
     */
    public function count(array $where = []): int
    {
        $builder = $this->query();

        if (count($where)) {
            $builder->addNestedWhereQuery($this->getNestedWhereConditions($builder->getQuery(), $where));
        }

        return $builder->count();
    }

    /**
     * {@inheritdoc}
     */
    public function findWhere(array $fieldValues, ?SortOptions $sortOptions = null): ?IEntity
    {
        $builder = $this->query()
            ->when($sortOptions, function (Builder $query) use ($sortOptions) {
                return $query->orderBy($sortOptions->orderBy, $sortOptions->sortOrder);
            });

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
    protected function getNestedWhereConditions($builder, array $criteria)
    {
        $subQuery = $builder->forNestedWhere();
        foreach ($criteria as $key => $criterionData) {
            switch (true) {
                case $criterionData instanceof RelationCriterion:
                    if (!method_exists($this->model, $criterionData->relation)) {
                        throw new BadCriteriaException($this);
                    }

                    /**
                     * Target relation to check existence.
                     *
                     * @var Relation $relation
                     */
                    $relation = $this->model->{$criterionData->relation}();

                    $relationQuery = $relation->getRelationExistenceQuery(
                        $relation->getRelated()->newQueryWithoutRelationships(),
                        $relation->getParent()->newQuery()
                    );

                    $relationQuery->addNestedWhereQuery(
                        $this->getNestedWhereConditions($relationQuery->getQuery(), $criterionData->criteria)
                    );

                    $subQuery->addWhereExistsQuery($relationQuery->toBase(), $criterionData->boolean);
                    continue 2;
                case $criterionData instanceof Criterion:
                    $criterion = $criterionData;
                    break;
                case is_string($key)
                    && ((!is_array($criterionData) && !is_object($criterionData))
                        || $criterionData instanceof Carbon):
                    $criterion = new Criterion([Criterion::ATTRIBUTE => $key, Criterion::VALUE => $criterionData]);
                    break;
                case is_int($key) && is_array($criterionData) && $this->isNestedCriteria($criterionData):
                    $boolean = 'and';
                    if (isset($criterionData[Criterion::BOOLEAN])) {
                        $boolean = $criterionData[Criterion::BOOLEAN];
                        unset($criterionData[Criterion::BOOLEAN]);
                    }
                    $subQuery->addNestedWhereQuery(
                        $this->getNestedWhereConditions($subQuery, $criterionData),
                        $boolean
                    );
                    continue 2;
                default:
                    $criterion = $this->parseCriterion($criterionData);
            }

            if (!$this->isCriterionValid($criterion)) {
                throw new BadCriteriaException($this);
            }

            switch (true) {
                case $criterion instanceof RelationCriterion:
                    break;
                case $criterion->operator === 'in':
                    $subQuery->whereIn($criterion->attribute, $criterion->value, $criterion->boolean);
                    break;
                case $criterion->operator === 'not in':
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
     * Shows whether given criterion data is nested.
     *
     * @param array $criterionData Criterion data to check
     *
     * @return boolean
     */
    protected function isNestedCriteria(array $criterionData): bool
    {
        $isValid = true;

        foreach ($criterionData as $key => $possibleCriterion) {
            $isValid = $isValid &&
                (
                    (is_int($key) && is_array($possibleCriterion)) ||
                    ($key === Criterion::BOOLEAN && in_array($possibleCriterion, ['and', 'or']))
                );
        }

        return $isValid;
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
        $isSingleOperator = !is_array($criterion->value) &&
            in_array(strtolower($criterion->operator), $this->singleOperators);

        return is_string($criterion->attribute) &&
            is_string($criterion->boolean) &&
            ($isMultipleOperator || $isSingleOperator);
    }

    /**
     * Validates that provided entity can be served by this repository.
     *
     * @param IEntity $model Entity to validate
     *
     * @return void
     *
     * @throws RepositoryException
     */
    protected function validateServedEntity(IEntity $model): void
    {
        if (!$model instanceof $this->modelClass) {
            throw new RepositoryException($this, "This repository can serve only {$this->modelClass} entities.");
        }
    }
}
