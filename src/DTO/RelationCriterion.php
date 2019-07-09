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

    /**
     * Criterion to check on existence model relation.
     *
     * @param string $relation Relation name
     * @param Criterion[] $criteria Criteria to filter relation
     * @param string $boolean Relation with criteria on same level
     */
    public function __construct(string $relation, array $criteria, string $boolean = 'and')
    {
        parent::__construct([static::BOOLEAN => $boolean]);

        $this->relation = $relation;
        $this->criteria = $criteria;
    }
}
