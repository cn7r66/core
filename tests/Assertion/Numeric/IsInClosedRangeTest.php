<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Numeric;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Numeric\IsInClosedRange;

#[CoversClass(IsInClosedRange::class)]
final class IsInClosedRangeTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(int|float $number, int|float $min, int|float $max): void
    {
        static::expectNotToPerformAssertions();

        (new IsInClosedRange($min, $max))
        ->assert($number);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(int|float|string $number, int|float $min, int|float $max, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsInClosedRange($min, $max))
            ->assert($number);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(int|float $number, int|float $min, int|float $max): void
    {
        static::assertTrue(
            (new IsInClosedRange($min, $max))($number),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(int|float $number, int|float $min, int|float $max): void
    {
        static::assertFalse(
            (new IsInClosedRange($min, $max))($number),
        );
    }

    /** @return array<array<int|float>> */
    public static function provideSuccess(): array
    {
        return [
            [0, 0, 9],
            [9, 0, 9],
            [5, 0, 9],
            [1, 1, 1],
        ];
    }

    /** @return array<array{0:int|float, 1:int|float, 2:int|float, 3:string}> */
    public static function provideFailure(): array
    {
        return [
            [10, 0, 9, 'Expected number to be in closed range [0, 9]. Got 10.'],
            [9.0001, 0, 9, 'Expected number to be in closed range [0, 9]. Got 9.0001.'],
        ];
    }

    /** @return array<array{0:int|float|string, 1:int|float, 2:int|float, 3:string}> */
    public static function provideInvalid(): array
    {
        return [
            [5, 10, 0, 'Lower bound must be lower or equal than upper bound. Got [10, 0].'],
            ['String', 0, 10, 'Expected value to be either integer or float. Got string.'],
        ];
    }
}
