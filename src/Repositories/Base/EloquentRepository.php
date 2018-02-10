<?php

namespace Saritasa\Repositories\Base;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Saritasa\DTO\SortOptions;

/**
 * Eloquent model repository. Manages stored entities.
 * In addition to base repository allows to retrieve list of entities by passed rules with requested related data.
 */
class EloquentRepository extends Repository implements IEloquentRepository
{
    /**
     * Returns builder that satisfied requested conditions, with eager loaded requested relations and relations counts,
     * ordered by requested rules.
     *
     * @param array|null $with Which relations should be preloaded
     * @param array|null $withCounts Which related entities should be counted
     * @param array|null $where Conditions that retrieved entities should satisfy
     * @param null|SortOptions $sortOptions How list of item should be sorted
     *
     * @return Builder
     */
    protected function getWithBuilder(
        ?array $with,
        ?array $withCounts = null,
        ?array $where = null,
        ?SortOptions $sortOptions = null
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
    ): Collection {
        return $this->getWithBuilder($with, $withCounts, $where, $sortOptions)->get();
    }
}
