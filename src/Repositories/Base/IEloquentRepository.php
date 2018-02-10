<?php

namespace Saritasa\Repositories\Base;

use Illuminate\Support\Collection;
use Saritasa\DTO\SortOptions;

/**
 * Eloquent model repository contract. Manages stored entities.
 * In addition to base contract allows to retrieve list of entities by passed rules with requested related data.
 */
interface IEloquentRepository extends IRepository
{
    /**
     * Retrieve list of entities that satisfied $where conditions.
     *
     * @param array|null $with Which relations should be preloaded
     * @param array|null $withCounts Which related entities should be counted
     * @param array|null $where Conditions that retrieved entities should satisfy
     * @param null|SortOptions $sortOptions How list of item should be sorted
     *
     * @return Collection
     */
    public function getWith(
        ?array $with,
        ?array $withCounts = null,
        ?array $where = null,
        ?SortOptions $sortOptions = null
    ): Collection;
}
