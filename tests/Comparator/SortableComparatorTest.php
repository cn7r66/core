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
use Vivarium\Comparator\Priority;
use Vivarium\Comparator\SortableComparator;
use Vivarium\Comparator\ValueAndPriority;

#[CoversClass(SortableComparator::class)]
class SortableComparatorTest extends TestCase
{
    public function testCompare(): void
    {
        $comparator = new SortableComparator();

        $low    = new ValueAndPriority('low', Priority::LOW);
        $normal = new ValueAndPriority('normal', Priority::NORMAL);
        $high   = new ValueAndPriority('high', Priority::HIGH);

        static::assertLessThan(0, $comparator->compare($low, $high));
        static::assertGreaterThan(0, $comparator->compare($high, $low));
        static::assertEquals(0, $comparator->compare($normal, $normal));
        static::assertEquals(0, $comparator($normal, $normal));
    }
}
