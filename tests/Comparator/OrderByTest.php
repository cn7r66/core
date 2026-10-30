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
use Vivarium\Comparator\OrderBy;

use function array_map;

#[CoversClass(OrderBy::class)]
final class OrderByTest extends TestCase
{
    /** @param non-empty-list<int> $results */
    #[DataProvider('provideResults')]
    public function testCompare(array $results, int $sign): void
    {
        $comparators = array_map(
            static function (int $result): Comparator {
                $comparator = self::createStub(Comparator::class);
                $comparator->method('compare')->willReturn($result);

                return $comparator;
            },
            $results,
        );

        $orderBy = new OrderBy(...$comparators);

        self::assertSame($sign, $orderBy->compare('a', 'b') <=> 0);
    }

    public function testCompareStopsAtTheFirstDecision(): void
    {
        $first = self::createStub(Comparator::class);
        $first->method('compare')->willReturn(-1);

        $second = $this->createMock(Comparator::class);
        $second->expects(self::never())->method('compare');

        self::assertSame(-1, (new OrderBy($first, $second))->compare('a', 'b') <=> 0);
    }

    /** @return array<string, array{non-empty-list<int>, int}> */
    public static function provideResults(): array
    {
        return [
            'single comparator' => [[3], 1],
            'first decides' => [[-3, 7], -1],
            'tie goes to the next' => [[0, 4], 1],
            'tie goes to the last' => [[0, 0, -2], -1],
            'every comparator ties' => [[0, 0, 0], 0],
        ];
    }
}
