<?php

namespace Saritasa\LaravelRepositories\Entities;

use Illuminate\Database\Eloquent\Model;
use Saritasa\LaravelRepositories\Contracts\IEntity;

/**
 * Wrapper on top of Eloquent model.
 */
abstract class EloquentEntity extends Model implements IEntity
{
    /**
     * {@inheritDoc}
     */
    public function getParameter(string $name)
    {
        return $this->getAttribute($name);
    }

    /**
     * {@inheritDoc}
     */
    public function setParameter(string $name, $value): void
    {
        $this->setAttribute($name, $value);
    }
}
