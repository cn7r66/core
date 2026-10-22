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
use Vivarium\Comparator\FloatComparator;

#[CoversClass(FloatComparator::class)]
class FloatComparatorTest extends TestCase
{
    public function testCompare(): void
    {
        $comparator = new FloatComparator(0.00001);

        static::assertEquals(-1, $comparator->compare(0.24578, 2.24579));
        static::assertEquals(1, $comparator->compare(0.24579, 0.24578));
        static::assertEquals(0, $comparator->compare(0.245788, 0.245789));
        static::assertEquals(0, $comparator->compare(0.245788, 0.245788));

        $comparator = new FloatComparator(0.0000001);
        static::assertEquals(-1, $comparator->compare(0.245788, 0.245789));
        static::assertEquals(-1, $comparator(0.245788, 0.245789));
    }
}
