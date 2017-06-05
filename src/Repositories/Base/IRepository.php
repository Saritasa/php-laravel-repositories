<?php

namespace Saritasa\Repositories\Base;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Saritasa\DingoApi\Paging\CursorRequest;
use Saritasa\DingoApi\Paging\CursorResult;
use Saritasa\DingoApi\Paging\PagingInfo;

/**
 * Generic model repository contract - manages stored entities
 *
 * @property-read array $searchableFields - Fields, available for direct search by values
 * @property-read array $modelValidationRules - Model validation rules
 */
interface IRepository
{
    public function getModelClass(): string;
    public function getModelValidationRules(Model $model = null): array;

    public function findOrFail($id): Model;
    public function findOrNew($id): Model;
    public function findWhere(array $fieldValues); // : Model;
    public function create(Model $model): Model;
    public function save(Model $model): Model;
    public function delete(Model $model);
    public function get(): Collection;
    public function getWhere(array $fieldValues): Collection;
    public function getPage(PagingInfo $paging, array $fieldValues = null): LengthAwarePaginator;
    public function getCursorPage(CursorRequest $cursor, array $fieldValues = null): CursorResult;
}
