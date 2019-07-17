<?php

namespace Saritasa\LaravelRepositories\Contracts;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Entity contract.
 */
interface IEntity extends Arrayable
{
    /**
     * Returns entity primary key value.
     *
     * @return mixed
     */
    public function getKey();

    /**
     * Returns parameter by given name.
     *
     * @param string $name Name to get parameter
     *
     * @return mixed
     */
    public function getParameter(string $name);

    /**
     * Sets parameter.
     *
     * @param string $name Name of parameter to set
     * @param mixed $value Value to set
     *
     * @return void
     */
    public function setParameter(string $name, $value): void;
}
