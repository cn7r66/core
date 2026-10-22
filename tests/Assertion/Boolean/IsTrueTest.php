<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Boolean;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Boolean\IsTrue;
use Vivarium\Assertion\Exception\AssertionFailed;

#[CoversClass(IsTrue::class)]
final class IsTrueTest extends TestCase
{
    public function testAssert(): void
    {
        static::expectNotToPerformAssertions();

        (new IsTrue())
            ->assert(true);
    }

    public function testAssertException(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('Expected boolean to be true. Got false.');

        (new IsTrue())
            ->assert(false);
    }

    public function testAssertWithoutBoolean(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('Expected value to be boolean. Got int.');

        (new IsTrue())
            ->assert(42);
    }
}
