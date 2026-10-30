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
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Comparator\ByNumber;

use function array_slice;
use function is_nan;
use function usort;

use const INF;
use const NAN;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

#[CoversClass(ByNumber::class)]
final class ByNumberTest extends TestCase
{
    #[DataProvider('provideIntegers')]
    #[DataProvider('provideFloats')]
    #[DataProvider('provideMixed')]
    #[DataProvider('provideNonFinite')]
    public function testCompare(int|float $first, int|float $second, int $sign): void
    {
        $comparator = new ByNumber();

        self::assertSame($sign, $comparator->compare($first, $second) <=> 0);
        self::assertSame(-$sign, $comparator->compare($second, $first) <=> 0);
    }

    public function testCompareSortsNanLast(): void
    {
        $numbers = [NAN, 1, -INF, 0.5, INF, -1, NAN];

        usort($numbers, (new ByNumber())->compare(...));

        self::assertSame([-INF, -1, 0.5, 1, INF], array_slice($numbers, 0, 5));
        self::assertTrue(is_nan($numbers[5]));
        self::assertTrue(is_nan($numbers[6]));
    }

    #[DataProvider('provideNotNumbers')]
    public function testCompareRejectsNotNumberFirst(mixed $value): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessage('Expected value to be int or float.');

        // @phpstan-ignore argument.type
        (new ByNumber())->compare($value, 1);
    }

    #[DataProvider('provideNotNumbers')]
    public function testCompareRejectsNotNumberSecond(mixed $value): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessage('Expected value to be int or float.');

        // @phpstan-ignore argument.type
        (new ByNumber())->compare(1, $value);
    }

    /** @return array<string, array{mixed}> */
    public static function provideNotNumbers(): array
    {
        return [
            'numeric string' => ['1'],
            'bool' => [true],
            'null' => [null],
        ];
    }

    /** @return array<string, array{int, int, int}> */
    public static function provideIntegers(): array
    {
        return [
            'int less' => [1, 2, -1],
            'int equal' => [1, 1, 0],
            'negative before positive' => [-1, 1, -1],
            'negative before zero' => [-1, 0, -1],
            'int extremes' => [PHP_INT_MIN, PHP_INT_MAX, -1],
            'int extremes equal' => [PHP_INT_MAX, PHP_INT_MAX, 0],
        ];
    }

    /** @return array<string, array{float, float, int}> */
    public static function provideFloats(): array
    {
        return [
            'float less' => [0.1, 0.2, -1],
            'float equal' => [0.5, 0.5, 0],
            'no tolerance' => [1.0, 1.0000000000000002, -1],
            'zero and negative zero' => [0.0, -0.0, 0],
        ];
    }

    /** @return array<string, array{int|float, int|float, int}> */
    public static function provideMixed(): array
    {
        return [
            'int before float' => [1, 1.5, -1],
            'float before int' => [0.5, 1, -1],
            'int equal to float' => [2, 2.0, 0],
        ];
    }

    /** @return array<string, array{int|float, float, int}> */
    public static function provideNonFinite(): array
    {
        return [
            'infinities' => [-INF, INF, -1],
            'infinity equal to itself' => [INF, INF, 0],
            'int before infinity' => [PHP_INT_MAX, INF, -1],
            'negative infinity before int' => [PHP_INT_MIN, -INF, 1],
            'NAN after infinity' => [INF, NAN, -1],
            'NAN after negative infinity' => [-INF, NAN, -1],
            'NAN after int' => [PHP_INT_MAX, NAN, -1],
            'NAN equal to NAN' => [NAN, NAN, 0],
        ];
    }
}
