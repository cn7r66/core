<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Comparator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Comparator\IntegerComparator;

#[CoversClass(IntegerComparator::class)]
class IntegerComparatorTest extends TestCase
{
    public function testCompare(): void
    {
        $comparator = new IntegerComparator();

        static::assertEquals(-1, $comparator->compare(1, 2));
        static::assertEquals(1, $comparator->compare(2, 1));
        static::assertEquals(0, $comparator->compare(1, 1));
        static::assertEquals(0, $comparator(1, 1));
    }
}
