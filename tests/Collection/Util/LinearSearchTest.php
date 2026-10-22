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
use Vivarium\Collection\Util\LinearSearch;

#[CoversClass(LinearSearch::class)]
final class LinearSearchTest extends TestCase
{
    public function testSearch(): void
    {
        $linear = new LinearSearch();
        $arr    = [1, 2, 4, 5, 7, 8];

        static::assertSame(0, $linear->search($arr, 1));
        static::assertSame(5, $linear->search($arr, 8));
        static::assertSame(2, $linear->search($arr, 4));
        static::assertSame(-7, $linear->search($arr, 10));
    }

    public function testContains(): void
    {
        $linear = new LinearSearch();
        $arr    = [1, 2, 4, 5, 7, 8];

        static::assertTrue($linear->contains($arr, 1));
        static::assertTrue($linear->contains($arr, 8));
        static::assertTrue($linear->contains($arr, 4));
        static::assertFalse($linear->contains($arr, 10));
    }
}
