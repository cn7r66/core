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
use Vivarium\Assertion\Var\IsFloat;

#[CoversClass(IsFloat::class)]
final class IsFloatTest extends TestCase
{
        #[DataProvider('provideSuccess')]
    public function testAssert(mixed $var): void
    {
        static::expectNotToPerformAssertions();

        (new IsFloat())
            ->assert($var);
    }

    #[DataProvider('provideFailure')]
    public function testAssertException(mixed $var, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsFloat())
            ->assert($var);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(mixed $var): void
    {
        static::assertTrue(
            (new IsFloat())($var),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(mixed $var): void
    {
        static::assertFalse(
            (new IsFloat())($var),
        );
    }

    /** @return array<array<float>> */
    public static function provideSuccess(): array
    {
        return [
            [4.5],
            [4.0],
            [4.999999],
        ];
    }

    /** @return array<array{0:mixed, 1:string}> */
    public static function provideFailure(): array
    {
        return [
            [42, 'Expected value to be float. Got int.'],
            ['string', 'Expected value to be float. Got string.'],
        ];
    }
}
