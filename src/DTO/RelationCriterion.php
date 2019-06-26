<?php

namespace Saritasa\LaravelRepositories\DTO;

/**
 * Criterion to check on existence model relation.
 *
 * @property-read string $relation Relation name
 * @property-read Criterion[] $criteria Criteria to filter relation
 */
class RelationCriterion extends Criterion
{
    public const RELATION = 'relation';
    public const CRITERIA = 'criteria';

    /**
     * Relation name.
     *
     * @var string
     */
    protected $relation;

    /**
     * Criteria to filter relation.
     *
     * @var Criterion[]
     */
    protected $criteria = [];
}
