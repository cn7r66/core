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
use Vivarium\Assertion\Boolean\IsFalse;
use Vivarium\Assertion\Exception\AssertionFailed;

#[CoversClass(IsFalse::class)]
final class IsFalseTest extends TestCase
{
    public function testAssert(): void
    {
        static::expectNotToPerformAssertions();

        (new IsFalse())
            ->assert(false);
    }

    public function testAssertException(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('Expected boolean to be false. Got true');

        (new IsFalse())
            ->assert(true);
    }

    public function testAssertWithoutBoolean(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('Expected value to be boolean. Got int.');

        (new IsFalse())
            ->assert(42);
    }
}
