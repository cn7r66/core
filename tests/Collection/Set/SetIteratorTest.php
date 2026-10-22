<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Collection\Set;

use PHPUnit\Framework\Attributes\CoversClass;
use Vivarium\Collection\Pair\Pair;
use Vivarium\Collection\Set\SetIterator;
use Vivarium\Test\Collection\Iterator\IteratorTestCase;

#[CoversClass(SetIterator::class)]
class SetIteratorTest extends IteratorTestCase
{
    public function testSetIterator(): void
    {
        $values = [
            1 => 2,
            3 => 4,
            5 => 6,
            7 => 8,
        ];

        $pairs = [];
        foreach ($values as $key => $value) {
            $pairs[] = new Pair($key, $value);
        }

        $iterator = new SetIterator($pairs);

        $this->doTest($iterator, [0, 1, 2, 3], [1, 3, 5, 7], static fn (int $key): int => $key);
    }
}
