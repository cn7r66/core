<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Float;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Float\NearlyEquals;

use const INF;
use const NAN;
use const PHP_FLOAT_EPSILON;
use const PHP_FLOAT_MAX;
use const PHP_FLOAT_MIN;

#[CoversClass(NearlyEquals::class)]
final class NearlyEqualsTest extends TestCase
{
    #[DataProvider('provideMagnitudes')]
    #[DataProvider('provideAroundZero')]
    #[DataProvider('provideRangeExtremes')]
    #[DataProvider('provideBoundaries')]
    #[DataProvider('provideNonFinite')]
    public function testEquals(float $epsilon, float $first, float $second, bool $expected): void
    {
        $nearlyEquals = new NearlyEquals($epsilon);

        self::assertSame($expected, $nearlyEquals->equals($first, $second));
        self::assertSame($expected, $nearlyEquals->equals($second, $first));
    }

    public function testEqualsWithDefaultEpsilon(): void
    {
        $nearlyEquals = new NearlyEquals();

        self::assertTrue($nearlyEquals->equals(0.1 + 0.2, 0.3));
        self::assertTrue($nearlyEquals->equals(1.0, 1.0000000000000004));
        self::assertFalse($nearlyEquals->equals(1.0, 1.0 + 1e-12));
    }

    #[DataProvider('provideInvalidEpsilons')]
    public function testConstructorRejectsInvalidEpsilon(float $epsilon): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessage('Expected epsilon to be finite and non-negative.');

        new NearlyEquals($epsilon);
    }

    /** @return array<string, array{float, float, float, bool}> */
    public static function provideMagnitudes(): array
    {
        return [
            'big' => [1e-5, 1e6, 1000001.0, true],
            'big negative' => [1e-5, -1e6, -1000001.0, true],
            'big, too far' => [1e-5, 1e4, 10001.0, false],
            'big negative, too far' => [1e-5, -1e4, -10001.0, false],
            'mid' => [1e-5, 1.0000001, 1.0000002, true],
            'mid negative' => [1e-5, -1.000001, -1.000002, true],
            'mid, too far' => [1e-5, 1.0001, 1.0002, false],
            'mid negative, too far' => [1e-5, -1.0001, -1.0002, false],
            'small' => [1e-5, 1.000001e-9, 1.000002e-9, true],
            'small negative' => [1e-5, -1.000001e-9, -1.000002e-9, true],
            'small, too far' => [1e-5, 1.001e-12, 1.002e-12, false],
            'small negative, too far' => [1e-5, -1.001e-12, -1.002e-12, false],
            'rounding error' => [1e-5, 0.3, 0.30000003, true],
            'rounding error negative' => [1e-5, -0.3, -0.30000003, true],
        ];
    }

    /** @return array<string, array{float, float, float, bool}> */
    public static function provideAroundZero(): array
    {
        return [
            'zero' => [1e-5, 0.0, 0.0, true],
            'signed zero' => [1e-5, 0.0, -0.0, true],
            'negative zero' => [1e-5, -0.0, -0.0, true],
            'subnormal' => [0.01, 0.0, 1e-310, true],
            'subnormal negative' => [0.1, 0.0, -1e-310, true],
            'subnormal, too far' => [1e-6, 0.0, 1e-310, false],
            'subnormal negative, too far' => [1e-8, 0.0, -1e-310, false],
            'normal' => [1e-5, 0.0, 1e-8, false],
            'normal negative' => [1e-5, 0.0, -1e-8, false],
            'opposite signs' => [1e-5, 1e-8, -1e-8, false],
        ];
    }

    /** @return array<string, array{float, float, float, bool}> */
    public static function provideRangeExtremes(): array
    {
        return [
            'far apart below single precision' => [PHP_FLOAT_EPSILON, 1e-100, 5e-100, false],
            'close below single precision' => [1e-5, 1e-35, 1.0000001e-35, true],
            'close above single precision' => [1e-5, 1e300, 1.0000001e300, true],
            'close with an overflowing sum' => [1e-5, 1e308, 1.0000001e308, true],
            'far apart with an overflowing sum' => [1e-5, 1e308, 1.5e308, false],
            'opposite maxima' => [1e-5, PHP_FLOAT_MAX, -PHP_FLOAT_MAX, false],
            'smallest normals' => [0.5, PHP_FLOAT_MIN, 2 * PHP_FLOAT_MIN, true],
        ];
    }

    /** @return array<string, array{float, float, float, bool}> */
    public static function provideBoundaries(): array
    {
        return [
            'relative difference equal to epsilon' => [0.5, 1.0, 3.0, true],
            'relative difference above epsilon' => [0.4999, 1.0, 3.0, false],
            'absolute difference equal to epsilon' => [0.5, 0.0, PHP_FLOAT_MIN / 2, true],
            'absolute difference above epsilon' => [0.4999, 0.0, PHP_FLOAT_MIN / 2, false],
            'zero epsilon, same value' => [0.0, 1.0, 1.0, true],
            'zero epsilon, signed zero' => [0.0, 0.0, -0.0, true],
            'zero epsilon, next value' => [0.0, 1.0, 1.0000000000000002, false],
            'zero epsilon, smallest subnormal' => [0.0, 0.0, 5e-324, false],
        ];
    }

    /** @return array<string, array{float, float, float, bool}> */
    public static function provideNonFinite(): array
    {
        return [
            'infinity' => [1e-5, INF, INF, true],
            'negative infinity' => [1e-5, -INF, -INF, true],
            'opposite infinities' => [1e-5, INF, -INF, false],
            'infinity and max' => [1e-5, INF, PHP_FLOAT_MAX, false],
            'nan' => [1e-5, NAN, NAN, false],
            'nan and a number' => [1e-5, NAN, 1.0, false],
            'nan and infinity' => [1e-5, NAN, INF, false],
            'nan with zero epsilon' => [0.0, NAN, NAN, false],
        ];
    }

    /** @return array<string, array{float}> */
    public static function provideInvalidEpsilons(): array
    {
        return [
            'negative' => [-1e-5],
            'negative infinity' => [-INF],
            'infinity' => [INF],
            'nan' => [NAN],
        ];
    }
}
