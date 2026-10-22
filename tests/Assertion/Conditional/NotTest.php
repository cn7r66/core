<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Conditional;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Conditional\Not;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Var\IsString;

#[CoversClass(Not::class)]
final class NotTest extends TestCase
{
    public function testAssert(): void
    {
        static::expectNotToPerformAssertions();

        (new Not(new IsString()))
            ->assert(42);
    }

    public function testAssertException(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage(
            'Failed negating the assertion "Vivarium\Assertion\Var\IsString" with value "Hello World".',
        );

        (new Not(new IsString()))
            ->assert('Hello World');
    }

    public function testInvoke(): void
    {
        static::assertTrue((new Not(new IsString()))(42));
        static::assertFalse((new Not(new IsString()))('Hello World'));
    }
}
