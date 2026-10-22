<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Collection\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Collection\Util\BinarySearch;
use Vivarium\Comparator\IntegerComparator;

#[CoversClass(BinarySearch::class)]
final class BinarySearchTest extends TestCase
{
    public function testSearch(): void
    {
        $binary = new BinarySearch(new IntegerComparator());
        $arr    = [1, 2, 4, 5, 7, 8];

        static::assertSame(0, $binary->search($arr, 1));
        static::assertSame(5, $binary->search($arr, 8));
        static::assertSame(2, $binary->search($arr, 4));
        static::assertSame(-3, $binary->search($arr, 3));
    }

    public function testContains(): void
    {
        $binary = new BinarySearch(new IntegerComparator());
        $arr    = [1, 2, 4, 5, 7, 8];

        static::assertTrue($binary->contains($arr, 1));
        static::assertTrue($binary->contains($arr, 8));
        static::assertTrue($binary->contains($arr, 4));
        static::assertFalse($binary->contains($arr, 10));
    }
}
