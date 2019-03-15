<?php

namespace Saritasa\LaravelRepositories\DTO;

use Saritasa\Dto;
use Saritasa\Exceptions\InvalidEnumValueException;
use Saritasa\LaravelRepositories\Enums\OrderDirections;

/**
 * The model contains the sort order direction and the field to sort by.
 */
class SortOptions extends Dto
{
    /**
     * Order by attribute name.
     */
    public const ORDER_BY = 'orderBy';

    /**
     * Sort order attribute name;
     */
    public const SORT_ORDER = 'sortOrder';

    /**
     * The model contains the sort order direction and the field to sort by.
     *
     * @param string $orderBy Field name to sort records by
     * @param string $sortOrder Sort order direction (asc, desc)
     *
     * @throws InvalidEnumValueException
     */
    public function __construct(string $orderBy, string $sortOrder = OrderDirections::ASC)
    {
        parent::__construct([
            static::ORDER_BY => $orderBy,
            static::SORT_ORDER => (new OrderDirections($sortOrder))->__toString(),
        ]);
    }

    /**
     * Field name to sort records by.
     *
     * @var string
     */
    public $orderBy;

    /**
     * Sort order direction.
     *
     * @see OrderDirections
     *
     * @var string
     */
    public $sortOrder;
}
