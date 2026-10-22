<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Float\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Float\FloatingPoint;
use Vivarium\Float\NearlyEquals;

/**
 * Class NearlyEqualsTest, a new class description.
 */
#[CoversClass(NearlyEquals::class)]
final class NearlyEqualsTest extends TestCase
{
    public function testBigNumbers(): void
    {
        $nearlyEquals = new NearlyEquals(0.00001);

        static::assertTrue($nearlyEquals(1000000, 1000001));
        static::assertTrue($nearlyEquals(1000001, 1000000));
        static::assertTrue($nearlyEquals(-1000000, -1000001));
        static::assertTrue($nearlyEquals(-1000001, -1000000));

        static::assertFalse($nearlyEquals(-10000, -10001));
        static::assertFalse($nearlyEquals(-10001, -10000));
        static::assertFalse($nearlyEquals(10000, 10001));
        static::assertFalse($nearlyEquals(10001, 10000));
    }

    public function testMidNumbers(): void
    {
        $nearlyEquals = new NearlyEquals(0.00001);

        static::assertTrue($nearlyEquals(1.0000001, 1.0000002));
        static::assertTrue($nearlyEquals(1.0000002, 1.0000001));
        static::assertTrue($nearlyEquals(-1.000001, -1.000002));
        static::assertTrue($nearlyEquals(-1.000002, -1.000001));

        static::assertFalse($nearlyEquals(1.0002, 1.0001));
        static::assertFalse($nearlyEquals(1.0001, 1.0002));
        static::assertFalse($nearlyEquals(-1.0001, -1.0002));
        static::assertFalse($nearlyEquals(-1.0002, -1.0001));
    }

    public function testSmallNumbers(): void
    {
        $nearlyEquals = new NearlyEquals(0.00001);

        static::assertTrue($nearlyEquals(0.000000001000001, 0.000000001000002));
        static::assertTrue($nearlyEquals(0.000000001000002, 0.000000001000001));
        static::assertTrue($nearlyEquals(-0.000000001000001, -0.000000001000002));
        static::assertTrue($nearlyEquals(-0.000000001000002, -0.000000001000001));

        static::assertFalse($nearlyEquals(-0.000000000001002, -0.000000000001001));
        static::assertFalse($nearlyEquals(-0.000000000001001, -0.000000000001002));
        static::assertFalse($nearlyEquals(0.000000000001002, 0.000000000001001));
        static::assertFalse($nearlyEquals(0.000000000001001, 0.000000000001002));
    }

    public function testSmallDiffs(): void
    {
        $nearlyEquals = new NearlyEquals(0.00001);

        static::assertFalse($nearlyEquals(2.1754943508223E-38, 1.1754943508223E-38));
        static::assertTrue($nearlyEquals(0.3, 0.30000003));
        static::assertTrue($nearlyEquals(-0.3, -0.30000003));
    }

    public function testInvolvingZero(): void
    {
        $nearlyEquals = new NearlyEquals(0.00001);

        static::assertTrue($nearlyEquals(0, 0));
        static::assertTrue($nearlyEquals(0, -0));
        static::assertTrue($nearlyEquals(-0, -0));

        static::assertTrue($nearlyEquals(0.0, 1e-40, 0.01));
        static::assertTrue($nearlyEquals(1e-40, 0.0, 0.01));
        static::assertTrue($nearlyEquals(0.0, -1e-40, 0.1));
        static::assertTrue($nearlyEquals(-1e-40, 0.0, 0.1));

        static::assertFalse($nearlyEquals(0.00000001, 0.0));
        static::assertFalse($nearlyEquals(0.0, 0.00000001));
        static::assertFalse($nearlyEquals(-0.00000001, 0.0));
        static::assertFalse($nearlyEquals(0.0, -0.00000001));

        static::assertFalse($nearlyEquals(1e-40, 0.0, 0.000001));
        static::assertFalse($nearlyEquals(0.0, 1e-40, 0.000001));
        static::assertFalse($nearlyEquals(-1e-40, 0.0, 0.00000001));
        static::assertFalse($nearlyEquals(0.0, -1e-40, 0.00000001));
    }

    public function testMutants(): void
    {
        $nearlyEquals = new NearlyEquals(0.5);

        static::assertTrue($nearlyEquals(FloatingPoint::FLOAT_MIN, 2 * FloatingPoint::FLOAT_MIN));
    }
}
