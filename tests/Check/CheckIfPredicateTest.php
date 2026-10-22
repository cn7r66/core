<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Check;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Check\CheckIfPredicate;

#[CoversClass(CheckIfPredicate::class)]
final class CheckIfPredicateTest extends TestCase
{
    public function testIsTrue(): void
    {
        static::assertTrue(CheckIfPredicate::isTrue(true));
        static::assertFalse(CheckIfPredicate::isTrue(false));
    }

    public function testIsFalse(): void
    {
        static::assertTrue(CheckIfPredicate::isFalse(false));
        static::assertFalse(CheckIfPredicate::isFalse(true));
    }
}
