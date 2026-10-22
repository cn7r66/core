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
use Vivarium\Assertion\Conditional\All;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\String\Contains;
use Vivarium\Assertion\String\IsLongAtLeast;
use Vivarium\Assertion\Var\IsString;

#[CoversClass(All::class)]
final class AllTest extends TestCase
{
    public function testAssert(): void
    {
        static::expectNotToPerformAssertions();

        (new All(
            new IsString(),
            new IsLongAtLeast(5),
        ))->assert('Hello');
    }

    public function testAssertException(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('Expected string to be long at least 10. Got 6.');

        (new All(
            new IsLongAtLeast(10),
            new Contains('Random'),
        ))->assert('Random');
    }

    public function testAssertFailLater(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('Expected string to be long at least 5. Got 2.');

        (new All(
            new IsString(),
            new IsLongAtLeast(5),
        ))->assert('Hi');
    }

    public function testInvoke(): void
    {
        $assertion = new All(
            new IsString(),
            new IsLongAtLeast(5),
        );

        static::assertTrue($assertion('Hello'));
    }

    public function testInvokeFail(): void
    {
        $assertion = new All(
            new IsString(),
            new IsLongAtLeast(5),
        );

        static::assertFalse($assertion(7));
    }
}
