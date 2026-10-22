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
use Vivarium\Assertion\Numeric\IsGreaterOrEqualThan;

#[CoversClass(IsGreaterOrEqualThan::class)]
final class IsGreaterOrEqualThanTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(int|float $test, int|float $limit): void
    {
        static::expectNotToPerformAssertions();

        (new IsGreaterOrEqualThan($limit))
            ->assert($test);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(int|float|string $test, int|float $limit, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsGreaterOrEqualThan($limit))
            ->assert($test);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(int|float $test, int|float $limit): void
    {
        static::assertTrue(
            (new IsGreaterOrEqualThan($limit))($test),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(int|float $test, int|float $limit): void
    {
        static::assertFalse(
            (new IsGreaterOrEqualThan($limit))($test),
        );
    }

    /** @return array<array<int|float>> */
    public static function provideSuccess(): array
    {
        return [
            [10, 10],
            [11, 10],
            [42, 10],
        ];
    }

    /** @return array<array{0:int|float, 1:int|float, 2:string}> */
    public static function provideFailure(): array
    {
        return [
            [3, 10, 'Expected number to be greater or equal than 10. Got 3.'],
            [9.99, 10, 'Expected number to be greater or equal than 10. Got 9.99.'],
        ];
    }

    /** @return array<array{0:string, 1:int|float, 2:string}> */
    public static function provideInvalid(): array
    {
        return [
            ['String', 10, 'Expected value to be either integer or float. Got string.'],
        ];
    }
}
