<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Collection\Pair;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Collection\Pair\Pair;

#[CoversClass(Pair::class)]
class PairTest extends TestCase
{
    public function testGet(): void
    {
        $pair = new Pair('a', 1);

        static::assertEquals('a', $pair->getKey());
        static::assertEquals(1, $pair->getValue());
    }

    public function testEquals(): void
    {
        $pair1 = new Pair('a', 1);
        $pair2 = new Pair('a', 1);
        $pair3 = new Pair('b', 2);

        static::assertTrue($pair1->equals($pair1));
        static::assertTrue($pair1->equals($pair2));
        static::assertFalse($pair1->equals($pair3));
        static::assertFalse($pair1->equals(new stdClass()));
    }

    public function testHash(): void
    {
        $pair = new Pair('a', 1);

        static::assertEquals('7b48edecef9b6487b09facffac1912fdc88c4cbe', $pair->hash());
    }
}
