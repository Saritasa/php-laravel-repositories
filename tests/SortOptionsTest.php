<?php

namespace Saritasa\Repositories\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\SQLiteConnection;
use PDO;
use PHPUnit\Framework\TestCase;
use Saritasa\DTO\SortOptions;
use Saritasa\Exceptions\NotImplementedException;

/**
 * Check SortOptions class.
 */
class SortOptionsTest extends TestCase
{
    /**
     * Test that sort option object handles passed values.
     */
    public function testPassedValues() {
        $fieldName = 'name';
        $sortOrder = 'desc';

        $sortOptions = new SortOptions($fieldName, $sortOrder);

        $this->assertEquals($fieldName, $sortOptions->orderBy);
        $this->assertEquals($sortOrder, $sortOptions->sortOrder);
    }

    /**
     * Test that sort options object doesn't support invalid sort orders
     */
    public function testUnsupportedSortOrder(){
        $this->expectException(\UnexpectedValueException::class);

        new SortOptions('name', 'smartOrder');
    }
}
