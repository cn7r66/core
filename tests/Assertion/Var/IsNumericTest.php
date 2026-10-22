<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Var;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Var\IsNumeric;

#[CoversClass(IsNumeric::class)]
final class IsNumericTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(mixed $var): void
    {
        static::expectNotToPerformAssertions();

        (new IsNumeric())
            ->assert($var);
    }

    #[DataProvider('provideFailure')]
    public function testAssertException(mixed $var, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsNumeric())
            ->assert($var);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(mixed $var): void
    {
        static::assertTrue(
            (new IsNumeric())($var),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(mixed $var): void
    {
        static::assertFalse(
            (new IsNumeric())($var),
        );
    }

    /** @return array<array<int|float>> */
    public static function provideSuccess(): array
    {
        return [
            [1],
            [0],
            [-1],
            [4.0],
            [4.5],
            [4.99999],
        ];
    }

    /** @return array<array{0:mixed, 1:string}> */
    public static function provideFailure(): array
    {
        return [
            [[], 'Expected value to be either integer or float. Got array.'],
            ['String', 'Expected value to be either integer or float. Got string.'],
        ];
    }
}
