<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Collection\MultiMap;

use ArrayIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Vivarium\Collection\Map\MapIterator;
use Vivarium\Collection\MultiMap\MultiMapIterator;
use Vivarium\Collection\Pair\Pair;
use Vivarium\Collection\Sequence\ArraySequence;
use Vivarium\Equality\HashBuilder;
use Vivarium\Test\Collection\Iterator\IteratorTestCase;
use Vivarium\Test\Collection\Stub\Key;

#[CoversClass(MultiMapIterator::class)]
final class MultiMapIteratorTest extends IteratorTestCase
{
    public function testMultiMapIterator(): void
    {
        $map = static function (int $key): Key {
            return new Key($key);
        };

        $values = [
            1 => new ArraySequence(1, 2),
            3 => new ArraySequence(4, 5),
        ];

        $pairs = [];
        foreach ($values as $key => $value) {
            $hash = (new HashBuilder())
                ->append($map($key))
                ->getHashCode();

            $pairs[$hash] = new Pair($map($key), $value);
        }

        $iterator = new MultiMapIterator(
            new MapIterator($pairs),
        );

        $this->doTest($iterator, [[$map(1), 1], [$map(1), 2], [$map(3), 4], [$map(3), 5]]);
    }

    /**
     * @param array<string, ArraySequence<int>> $collections
     * @param list<array{string, int}>          $expected
     */
    #[DataProvider('provideEmptyCollections')]
    public function testIteratorSkipsEmptyCollections(array $collections, array $expected): void
    {
        $iterator = new MultiMapIterator(new ArrayIterator($collections));

        static::assertSame($expected, $this->pairsOf($iterator));

        $iterator->rewind();

        static::assertSame($expected, $this->pairsOf($iterator));
    }

    /** @return array<string, array{array<string, ArraySequence<int>>, list<array{string, int}>}> */
    public static function provideEmptyCollections(): array
    {
        return [
            'empty first' => [
                ['a' => new ArraySequence(), 'b' => new ArraySequence(1, 2)],
                [['b', 1], ['b', 2]],
            ],
            'empty in the middle' => [
                ['a' => new ArraySequence(1), 'b' => new ArraySequence(), 'c' => new ArraySequence(3)],
                [['a', 1], ['c', 3]],
            ],
            'empty last' => [
                ['a' => new ArraySequence(1), 'b' => new ArraySequence()],
                [['a', 1]],
            ],
            'all empty' => [
                ['a' => new ArraySequence(), 'b' => new ArraySequence()],
                [],
            ],
            'no collections' => [[], []],
        ];
    }

    /**
     * @param MultiMapIterator<string, int> $iterator
     *
     * @return list<array{string, int}>
     */
    private function pairsOf(MultiMapIterator $iterator): array
    {
        $pairs = [];
        while ($iterator->valid()) {
            $pairs[] = [$iterator->key(), $iterator->current()];
            $iterator->next();
        }

        return $pairs;
    }
}
