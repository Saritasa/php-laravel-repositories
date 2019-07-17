<?php

namespace Saritasa\LaravelRepositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Saritasa\DingoApi\Paging\CursorRequest;
use Saritasa\DingoApi\Paging\CursorResult;
use Saritasa\DingoApi\Paging\PagingInfo;
use Saritasa\LaravelRepositories\DTO\SortOptions;
use Saritasa\LaravelRepositories\Exceptions\BadCriteriaException;
use Saritasa\LaravelRepositories\Exceptions\ModelNotFoundException;
use Saritasa\LaravelRepositories\Exceptions\RepositoryException;

/**
 * Generic model repository contract - manages stored entities.
 */
interface IRepository
{
    /**
     * Returns managed by this repository entity class name.
     *
     * @return string
     */
    public function getEntityClass(): string;

    /**
     * Find entity by their id.
     *
     * @param string|int $id Entity id to find
     *
     * @return IEntity
     *
     * @throws ModelNotFoundException
     * @throws RepositoryException
     */
    public function findOrFail($id): IEntity;

    /**
     * Returns first entity matching given filters.
     *
     * @param array $fieldValues Filters collection
     * @param SortOptions|null $sortOptions How list of items should be sorted
     *
     * @return IEntity|null
     *
     * @throws BadCriteriaException
     */
    public function findWhere(array $fieldValues, ?SortOptions $sortOptions = null): ?IEntity;

    /**
     * Create entity in storage.
     *
     * @param IEntity $entity Entity to create
     *
     * @return IEntity
     *
     * @throws RepositoryException
     */
    public function create(IEntity $entity): IEntity;

    /**
     * Save entity in storage.
     *
     * @param IEntity $entity Entity to save
     *
     * @return IEntity
     *
     * @throws RepositoryException
     */
    public function save(IEntity $entity): IEntity;

    /**
     * Delete entity in storage.
     *
     * @param IEntity $entity Entity to delete
     *
     * @return void
     *
     * @throws RepositoryException
     */
    public function delete(IEntity $entity): void;

    /**
     * Returns entities list.
     *
     * @param array $fieldValues Filters collection
     * @param SortOptions|null $sortOptions How list of items should be sorted
     *
     * @return Collection|IEntity[]
     *
     * @throws BadCriteriaException
     */
    public function get(array $fieldValues = [], ?SortOptions $sortOptions = null): Collection;

    /**
     * Get entities collection as pagination.
     *
     * @param PagingInfo $paging Paging information
     * @param array $fieldValues Filters collection
     * @param SortOptions|null $sortOptions How list of items should be sorted
     *
     * @return LengthAwarePaginator
     *
     * @throws BadCriteriaException
     * @throws InvalidArgumentException
     */
    public function getPage(
        PagingInfo $paging,
        array $fieldValues = [],
        ?SortOptions $sortOptions = null
    ): LengthAwarePaginator;

    /**
     * Get entities collection as cursor.
     *
     * @param CursorRequest $cursor Request with cursor data
     * @param array $fieldValues Filters collection
     * @param SortOptions|null $sortOptions How list of items should be sorted
     *
     * @return CursorResult
     *
     * @throws BadCriteriaException
     */
    public function getCursorPage(
        CursorRequest $cursor,
        array $fieldValues = [],
        ?SortOptions $sortOptions = null
    ): CursorResult;

    /**
     * Return entities count.
     *
     * @param array $fieldValues Conditions that retrieved entities should satisfy
     *
     * @return integer
     *
     * @throws BadCriteriaException
     */
    public function count(array $fieldValues = []): int;
}
