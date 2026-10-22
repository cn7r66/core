<?php

/**
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Equality;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Equality\Equal;
use Vivarium\Equality\Equality;

#[CoversClass(Equal::class)]
final class EqualTest extends TestCase
{
    public function testAreEquals(): void
    {
        static::assertTrue(Equal::areEquals(42, 42));
        static::assertTrue(Equal::areEquals('z', 'z'));
    }

    public function testHash(): void
    {
        $equality = $this->createMock(Equality::class);
        $equality
            ->expects(static::once())
            ->method('hash')
            ->willReturn('79169da20d8365b605a4d0802300cb30019eec9f');

        static::assertEquals('061dcb8bab856f8eb86506be2d4d9dfec34f9948', Equal::hash($equality));
    }
}
