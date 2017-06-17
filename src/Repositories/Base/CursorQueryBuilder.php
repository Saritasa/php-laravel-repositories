<?php

namespace Saritasa\Repositories\Base;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Saritasa\DingoApi\Paging\CursorRequest;
use Saritasa\DingoApi\Paging\CursorResult;
use Saritasa\DingoApi\Paging\CursorResultAuto;

class CursorQueryBuilder
{
    protected $cursorRequest;
    protected $model;
    /** @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder */
    protected $originalQuery;
    /**
     * Wrap the query to support cursor pagination with custom sort.
     * @param CursorRequest $cursorRequest Requested cursor parameters
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     */
    function __construct(CursorRequest $cursorRequest, $query)
    {
        $this->cursorRequest = $cursorRequest;
        $this->model = $query->getModel();
        $this->originalQuery = $this->getBaseQuery($query);
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
     * @return  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function buildQuery()
    {
        $tmpQuery = $this->originalQuery->cloneWithoutBindings(['where'])->crossJoin(DB::raw('(SELECT @row := 1) as r'));
        $idKey = CursorResultAuto::ROW_NUM_COLUMN;
        $wrappedQuery = DB::table(
            DB::raw("(SELECT *, (@row := @row+1) as {$idKey} FROM (" . $tmpQuery->toSql() . ") as t1) as t2")
        )->mergeBindings($this->originalQuery);
        /** @var Model $fakeModel */
        $fakeModel = new $this->model;
        $fakeModel->setKeyName($idKey);
        $fakeModel->setTable(DB::raw("(" . $wrappedQuery->toSql() . ") AS " . $this->model->getTable()));
        /** @var Builder $query */
        $query = $fakeModel->newQuery();

        $baseQuery = $this->getBaseQuery($query);
        // Remove default wheres - they are already inside wrapped query
        $baseQuery->wheres = [];
        $baseQuery->bindings = [];

        $query->mergeBindings($wrappedQuery)
            ->where($idKey, '>', $this->cursorRequest->current)
            ->take($this->cursorRequest->pageSize);
        return $query;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @return Builder
     */

    protected function getBaseQuery($query) {
        return ($query instanceof Builder) ? $query : $query->getQuery();
    }
}