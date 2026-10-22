<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Conditional;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Conditional\IsNotNull;
use Vivarium\Assertion\Exception\AssertionFailed;

#[CoversClass(IsNotNull::class)]
final class IsNotNullTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(mixed $value): void
    {
        static::expectNotToPerformAssertions();

        (new IsNotNull())
            ->assert($value);
    }

    public function testAssertException(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('Expected value to be not null.');

        (new IsNotNull())
            ->assert(null);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(mixed $value): void
    {
        static::assertTrue(
            (new IsNotNull())($value),
        );
    }

    public function testInvokeFailure(): void
    {
        static::assertFalse(
            (new IsNotNull())(null),
        );
    }

    /** @return array<array{0: mixed}> */
    public static function provideSuccess(): array
    {
        return [
            ['string'],
            [42],
            [0],
            [false],
            [''],
            [[]],
            [0.0],
        ];
    }
}
