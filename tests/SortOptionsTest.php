<?php

namespace Saritasa\Repositories\Tests;

use PHPUnit\Framework\TestCase;
use Saritasa\DTO\SortOptions;
use Saritasa\Exceptions\InvalidEnumValueException;

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
        $this->expectException(InvalidEnumValueException::class);

        new SortOptions('name', 'smartOrder');
    }
}
