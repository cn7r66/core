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
use Vivarium\Comparator\StringComparator;

#[CoversClass(StringComparator::class)]
class StringComparatorTest extends TestCase
{
    public function testCompare(): void
    {
        $comparator = new StringComparator();

        static::assertLessThan(0, $comparator->compare('a', 'z'));
        static::assertGreaterThan(0, $comparator->compare('z', 'a'));
        static::assertEquals(0, $comparator->compare('a', 'a'));
        static::assertEquals(0, $comparator('a', 'a'));
    }
}
