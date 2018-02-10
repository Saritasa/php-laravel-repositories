<?php

namespace Saritasa\DTO;

use Saritasa\Enums\OrderDirections;
use Saritasa\Transformers\DtoModel;

/**
 * The model contains the sort order direction and the field to sort by.
 */
class SortOptions extends DtoModel
{
    /**
     * Create SortOptionsData model.
     *
     * @param string|null $orderBy Field name to sort records by
     * @param string|null $sortOrder Sort order direction (asc, desc)
     */
    public function __construct(string $orderBy = null, ?string $sortOrder = OrderDirections::ASC)
    {
        parent::__construct([
            'orderBy' => $orderBy,
            'sortOrder' => (string)new OrderDirections($sortOrder),
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
     * @var string
     */
    public $sortOrder;
}
