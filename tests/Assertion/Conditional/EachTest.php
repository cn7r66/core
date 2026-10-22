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
use Traversable;
use Vivarium\Assertion\Conditional\Each;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Numeric\IsInClosedRange;
use Vivarium\Assertion\Object\IsInstanceOf;
use Vivarium\Assertion\String\IsLongAtLeast;
use Vivarium\Assertion\Var\IsInteger;

#[CoversClass(Each::class)]
final class EachTest extends TestCase
{
    public function testAssert(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('Element at index 0 failed the assertion.');

        $stub = $this->createStub(Traversable::class);

        (new Each(
            new IsInstanceOf(Traversable::class),
        ))->assert([$stub, $stub, $stub]);

        (new Each(
            new IsInClosedRange(0, 9),
        ))->assert([0, 9, 3, 5]);

        (new Each(
            new IsInClosedRange(0, 9),
        ))->assert([42, 9, 3, 5]);
    }

    public function testAssertFailLater(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('Element at index 2 failed the assertion.');

        (new Each(
            new IsInteger(),
        ))->assert([0, 9, '3', 42]);
    }

    public function testAssertWithoutArray(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('Expected value to be array. Got int.');

        (new Each(
            new IsLongAtLeast(27),
        ))->assert(42);
    }

    public function testInvoke(): void
    {
        $eachInClosedRange = new Each(
            new IsInClosedRange(0, 9),
        );

        static::assertTrue($eachInClosedRange([0, 9, 3, 5]));
        static::assertFalse($eachInClosedRange([0, 9, 3, 42]));
    }

    public function testInvokeWithoutArray(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('Expected value to be array. Got int.');

        (new Each(
            new IsLongAtLeast(27),
        ))(42);
    }
}
