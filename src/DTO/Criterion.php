<?php

namespace Saritasa\LaravelRepositories\DTO;

use Saritasa\Dto;

/**
 * Data retrieving criteria that retrieved items should match.
 *
 * @property-read string $attribute Attribute that should satisfy criterion value
 * @property-read string $operator Operator that is used to check whether criterion value matches item attribute or not
 * @property-read mixed|null $value Criterion value that retrieved items attribute should satisfy/match
 * @property-read string $boolean Relation with criteria on same level
 */
class Criterion extends Dto
{
    public const ATTRIBUTE = 'attribute';
    public const OPERATOR = 'operator';
    public const VALUE = 'value';
    public const BOOLEAN = 'boolean';

    /**
     * Relation with criteria on same level.
     *
     * @var string
     */
    protected $boolean = 'and';

    /**
     * Operator that is used to check whether criterion value matches item attribute or not.
     *
     * @var string
     */
    protected $operator = '=';

    /**
     * Criterion value that retrieved items attribute should satisfy/match.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Attribute that should satisfy criterion value.
     *
     * @var string
     */
    protected $attribute;
}
