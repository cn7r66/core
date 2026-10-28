<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Equality;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Equality\Equal;
use Vivarium\Equality\HashBuilder;
use Vivarium\Test\Equality\Stub\EqualityStub;

#[CoversClass(Equal::class)]
final class EqualTest extends TestCase
{
    public function testAreEquals(): void
    {
        self::assertTrue(Equal::areEquals(new EqualityStub(), new EqualityStub()));
        self::assertFalse(Equal::areEquals(1, '1'));
    }

    public function testHash(): void
    {
        $stub = new EqualityStub();

        self::assertSame((new HashBuilder())->append($stub)->getHashCode(), Equal::hash($stub));
    }
}
