<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Comparator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Comparator\Comparator;
use Vivarium\Comparator\Reversed;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

#[CoversClass(Reversed::class)]
final class ReversedTest extends TestCase
{
    #[DataProvider('provideInnerResults')]
    public function testCompare(int $before, int $after, int $sign): void
    {
        $inner = self::createStub(Comparator::class);
        $inner->method('compare')->willReturnMap([
            ['a', 'b', $before],
            ['b', 'a', $after],
        ]);

        $reversed = new Reversed($inner);

        self::assertSame($sign, $reversed->compare('a', 'b') <=> 0);
        self::assertSame(-$sign, $reversed->compare('b', 'a') <=> 0);
    }

    /** @return array<string, array{int, int, int}> */
    public static function provideInnerResults(): array
    {
        return [
            'before becomes after' => [-1, 1, 1],
            'after becomes before' => [1, -1, -1],
            'equivalent stays equivalent' => [0, 0, 0],
            'extremes do not overflow' => [PHP_INT_MIN, PHP_INT_MAX, 1],
        ];
    }
}
