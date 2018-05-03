<?php

namespace Saritasa\LaravelRepositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Saritasa\DingoApi\Paging\CursorRequest;
use Saritasa\DingoApi\Paging\CursorResult;
use Saritasa\DingoApi\Paging\PagingInfo;
use Saritasa\LaravelRepositories\DTO\SortOptions;
use Saritasa\LaravelRepositories\Exceptions\ModelNotFoundException;
use Saritasa\LaravelRepositories\Exceptions\RepositoryException;

/**
 * Generic model repository contract - manages stored entities
 *
 * @property-read array $searchableFields - Fields, available for direct search by values
 */
interface IRepository
{
    /**
     * Returns model class of current repository.
     *
     * @return string
     */
    public function getModelClass(): string;

    /**
     * Returns model validation rules.
     *
     * @return array
     */
    public function getModelValidationRules(): array;

    /**
     * Find model by their id.
     *
     * @param string|int $id Model id to find
     *
     * @return Model
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail($id): Model;

    /**
     * Returns first model matching given filters.
     *
     * @param array $fieldValues Filters collection
     * @return Model|null
     */
    public function findWhere(array $fieldValues): ?Model;

    /**
     * Create modal in storage.
     *
     * @param Model $entity Model to create
     * @return Model
     *
     * @throws RepositoryException
     */
    public function create(Model $entity): Model;

    /**
     * Save model in storage.
     *
     * @param Model $entity Model to save
     *
     * @return Model
     *
     * @throws RepositoryException
     */
    public function save(Model $entity): Model;

    /**
     * Delete model in storage.
     *
     * @param Model $entity Model to delete
     * @return void
     *
     * @throws RepositoryException
     */
    public function delete(Model $entity): void;

    /**
     * Returns models list.
     *
     * @return Collection
     */
    public function get(): Collection;

    /**
     * Returns models list matching given filters.
     *
     * @param array $fieldValues Filters collection
     *
     * @return Collection
     */
    public function getWhere(array $fieldValues): Collection;

    /**
     * Get models collection as pagination.
     *
     * @param PagingInfo $paging Paging information
     * @param array|null $fieldValues Filters collection
     *
     * @return LengthAwarePaginator
     */
    public function getPage(PagingInfo $paging, array $fieldValues = []): LengthAwarePaginator;

    /**
     * Get models collection as cursor.
     *
     * @param CursorRequest $cursor Request with cursor data
     * @param array|null $fieldValues Filters collection
     *
     * @return CursorResult
     */
    public function getCursorPage(CursorRequest $cursor, array $fieldValues = []): CursorResult;

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
        array $withCounts = [],
        array $where = [],
        SortOptions $sortOptions = null
    ): Collection;

    /**
     * Return entities count.
     *
     * @return integer
     */
    public function count(): int;
}
