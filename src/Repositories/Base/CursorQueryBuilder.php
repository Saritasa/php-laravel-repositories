<?php

namespace Saritasa\Repositories\Base;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\DB;
use Saritasa\DingoApi\Paging\CursorRequest;
use Saritasa\DingoApi\Paging\CursorResult;
use Saritasa\DingoApi\Paging\CursorResultAuto;

class CursorBuilder
{
    protected $cursorRequest;
    protected $model;
    /** @var EloquentBuilder|QueryBuilder $query */
    protected $originalQuery;
    protected $idKey;

    /**
     * Wrap the query to support cursor pagination with custom sort.
     * @param CursorRequest $cursorRequest Requested cursor parameters
     * @param EloquentBuilder|QueryBuilder $query
     */
    function __construct(CursorRequest $cursorRequest, $query)
    {
        $this->cursorRequest = $cursorRequest;
        $this->model = $query->getModel();
        $this->originalQuery = $this->getBaseQuery($query);
        $this->idKey = CursorResultAuto::ROW_NUM_COLUMN;
    }

    /**
     * Return
     *
     * @return CursorResult
     */
    public function getCursor(): CursorResult
    {
        $query = $this->buildQuery();
        $items = $query->get();
        return new CursorResultAuto($this->cursorRequest, $items);
    }

    /**
     * @return EloquentBuilder|QueryBuilder
     */
    public function buildQuery()
    {
        $wrappedQuery = $this->wrapWithRowCounter($this->originalQuery);
        $query = $this->getFakeModelQuery($wrappedQuery);

        $query->where($this->idKey, '>', $this->cursorRequest->current)
            ->take($this->cursorRequest->pageSize);
        return $query;
    }

    /**
     * @param EloquentBuilder|QueryBuilder $query
     * @return QueryBuilder
     */
    protected function wrapWithRowCounter($originalQuery)
    {
        $tmpQuery = $originalQuery->cloneWithoutBindings(['where'])
            ->crossJoin(DB::raw('(SELECT @row := 1) as r'));

        return DB::table(
            DB::raw("(SELECT *, (@row := @row+1) as {$this->idKey} FROM (" . $tmpQuery->toSql() . ") as t1) as t2")
        )->mergeBindings($originalQuery);
    }

    /**
     * @param QueryBuilder $wrappedQuery
     * @return EloquentBuilder|QueryBuilder
     */
    protected function getFakeModelQuery($wrappedQuery)
    {
        /** @var Model $fakeModel */
        $fakeModel = new $this->model;
        $fakeModel->setKeyName($this->idKey);
        $fakeModel->setTable(DB::raw("(" . $wrappedQuery->toSql() . ") AS " . $this->model->getTable()));

        /** @var Builder $modelQuery */
        $modelQuery = $fakeModel->newQuery();

        $baseQuery = $this->getBaseQuery($modelQuery);
        // Remove default wheres - they are already inside wrapped query
        $baseQuery->wheres = [];
        $baseQuery->bindings = [];

        $modelQuery->mergeBindings($wrappedQuery);
        return $modelQuery;
    }

    /**
     * @param EloquentBuilder|QueryBuilder $query $query
     * @return QueryBuilder
     */

    protected function getBaseQuery($query) {
        return ($query instanceof QueryBuilder) ? $query : $query->getQuery();
    }
}