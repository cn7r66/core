<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Collection\Map;

use PHPUnit\Framework\Attributes\CoversClass;
use Vivarium\Collection\Map\MapIterator;
use Vivarium\Collection\Pair\Pair;
use Vivarium\Equality\HashBuilder;
use Vivarium\Test\Collection\Iterator\IteratorTestCase;
use Vivarium\Test\Collection\Stub\Key;
use Vivarium\Test\Collection\Stub\KeyWithHashCollision;

use function array_keys;
use function array_values;

#[CoversClass(MapIterator::class)]
class MapIteratorTest extends IteratorTestCase
{
    public function testMapIterator(): void
    {
        $map = static function (int $key): Key {
            return new Key($key);
        };

        $expected = [
            1 => 2,
            3 => 4,
            5 => 6,
            7 => 8,
        ];

        $pairs = [];
        foreach ($expected as $key => $value) {
            $hash = (new HashBuilder())
                ->append($map($key))
                ->getHashCode();

            $pairs[$hash] = new Pair($map($key), $value);
        }

        $iterator = new MapIterator($pairs);

        $this->doTest($iterator, array_keys($expected), array_values($expected), $map);
    }

    public function testMapIteratorWithBuckets(): void
    {
        $map = static function (int $key): KeyWithHashCollision {
            return new KeyWithHashCollision($key);
        };

        $expected = [
            1 => 2,
            3 => 4,
            5 => 6,
            7 => 8,
            2 => 7,
            4 => 5,
            9 => 9,
            10 => 11,
        ];

        $pairs = [];
        foreach ($expected as $key => $value) {
            $hash = (new HashBuilder())
                ->append($map($key))
                ->getHashCode();

            $pair = new Pair(
                $map($key),
                $value,
            );

            if (! isset($pairs[$hash])) {
                $pairs[$hash] = [];
            }

            $pairs[$hash][] = $pair;
        }

        $iterator = new MapIterator($pairs);

        $this->doTest($iterator, array_keys($expected), array_values($expected), $map);
    }
}
