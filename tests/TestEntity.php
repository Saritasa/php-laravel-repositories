<?php

namespace Saritasa\LaravelRepositories\Tests;

use Illuminate\Database\Eloquent\Model;

/**
 * Entity for tests.
 *
 * @property string $field1
 * @property string $field2
 * @property string $field3
 */
class TestEntity extends Model
{
    public const FIELD_1 = 'field1';
    public const FIELD_2 = 'field2';
    public const FIELD_3 = 'field3';

    protected $table = 'test_table';

    protected $fillable = [
        self::FIELD_1,
        self::FIELD_2,
        self::FIELD_3,
    ];

    public function relation1()
    {
        $object = new class extends Model {
            protected $table = 'table1';
        };

        return $this->hasMany(get_class($object));
    }
}
