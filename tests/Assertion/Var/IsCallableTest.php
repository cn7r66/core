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
use Vivarium\Assertion\Var\IsCallable;
use Vivarium\Assertion\Var\IsInteger;

#[CoversClass(IsCallable::class)]
final class IsCallableTest extends TestCase
{
        #[DataProvider('provideSuccess')]
    public function testAssert(mixed $var): void
    {
        static::expectNotToPerformAssertions();

        (new IsCallable())
            ->assert($var);
    }

    #[DataProvider('provideFailure')]
    public function testAssertException(mixed $var, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsCallable())
            ->assert($var);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(mixed $var): void
    {
        static::assertTrue(
            (new IsCallable())($var),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(mixed $var): void
    {
        static::assertFalse(
            (new IsCallable())($var),
        );
    }

    /** @return array<array<callable>> */
    public static function provideSuccess(): array
    {
        return [
            [new IsInteger()],
            [
                static function (): int {
                    return 42;
                },
            ],
            [static fn (int $a): int => $a],
        ];
    }

    /** @return array<array{0:mixed, 1:string}> */
    public static function provideFailure(): array
    {
        return [
            [42, 'Expected value to be callable. Got int.'],
            ['string', 'Expected value to be callable. Got string.'],
        ];
    }
}
