<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Collection\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Collection\Util\BinarySearch;
use Vivarium\Collection\Util\Vector;
use Vivarium\Comparator\ByNumber;

#[CoversClass(Vector::class)]
final class VectorTest extends TestCase
{
    /**
     * @param list<int|float> $array
     * @param list<int|float> $expected
     */
    #[DataProvider('providePut')]
    public function testPut(array $array, int|float $element, array $expected): void
    {
        $search = new BinarySearch((new ByNumber())->compare(...));

        static::assertSame($expected, Vector::put($array, $element, $search));
    }

    /**
     * @param list<int> $array
     * @param list<int> $expected
     */
    #[DataProvider('provideInsertAtIndex')]
    public function testInsertAtIndex(array $array, int $element, int $index, array $expected): void
    {
        static::assertSame($expected, Vector::insertAtIndex($array, $element, $index));
    }

    #[DataProvider('provideInsertAtIndexOutOfRange')]
    public function testInsertAtIndexRejectsOutOfRange(int $index, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        Vector::insertAtIndex([3, 6, 1, 2, 5], 8, $index);
    }

    /** @return array<string, array{list<int|float>, int|float, list<int|float>}> */
    public static function providePut(): array
    {
        return [
            'append' => [[1, 2, 4, 5], 7, [1, 2, 4, 5, 7]],
            'prepend' => [[1, 2, 4, 5], 0, [0, 1, 2, 4, 5]],
            'insert inside' => [[1, 2, 4, 5], 3, [1, 2, 3, 4, 5]],
            'into empty' => [[], 3, [3]],
            'replace equal' => [[1, 2, 4, 5], 2.0, [1, 2.0, 4, 5]],
            'replace first' => [[1, 2, 4, 5], 1.0, [1.0, 2, 4, 5]],
        ];
    }

    /** @return array<string, array{list<int>, int, int, list<int>}> */
    public static function provideInsertAtIndex(): array
    {
        return [
            'inside' => [[3, 6, 1, 2, 5], 7, 2, [3, 6, 7, 1, 2, 5]],
            'first' => [[3, 6, 1, 2, 5], 1, 0, [1, 3, 6, 1, 2, 5]],
            'append at count' => [[3, 6, 1, 2, 5], 8, 5, [3, 6, 1, 2, 5, 8]],
            'equal element kept' => [[3, 6, 1, 2, 5], 6, 1, [3, 6, 6, 1, 2, 5]],
        ];
    }

    /** @return array<string, array{int, string}> */
    public static function provideInsertAtIndexOutOfRange(): array
    {
        return [
            'past count' => [6, 'Expected value to be in [0, 5]. Got int 6.'],
            'negative' => [-1, 'Expected value to be in [0, 5]. Got int -1.'],
        ];
    }
}
