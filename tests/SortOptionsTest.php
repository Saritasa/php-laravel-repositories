<?php

namespace Saritasa\LaravelRepositories\Tests;

use PHPUnit\Framework\TestCase;
use Saritasa\LaravelRepositories\DTO\SortOptions;
use Saritasa\Exceptions\InvalidEnumValueException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * Check SortOptions class.
 */
class SortOptionsTest extends TestCase
{
    /**
     * Test that sort option object handles passed values.
     *
     * @throws InvalidEnumValueException
     * @throws InvalidArgumentException
     */
    public function testPassedValues(): void
    {
        $fieldName = 'name';
        $sortOrder = 'desc';

        $sortOptions = new SortOptions($fieldName, $sortOrder);

        $this->assertEquals($fieldName, $sortOptions->orderBy);
        $this->assertEquals($sortOrder, $sortOptions->sortOrder);
    }

    /**
     * Test that sort options object doesn't support invalid sort orders
     *
     * @throws InvalidEnumValueException
     */
    public function testUnsupportedSortOrder(): void
    {
        $this->expectException(InvalidEnumValueException::class);

        new SortOptions('name', 'smartOrder');
    }
}
