<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Collection\MultiMap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Collection\Collection;
use Vivarium\Collection\MultiMap\MultiMap;
use Vivarium\Collection\MultiMap\MultiValueMap;
use Vivarium\Collection\Pair\Pair;
use Vivarium\Collection\Sequence\ArraySequence;
use Vivarium\Equality\Equal;

#[CoversClass(MultiValueMap::class)]
final class MultiValueMapTest extends TestCase
{
    public function testConstructor(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
            new Pair('b', new ArraySequence(3, 4, 5)),
            new Pair('c', new ArraySequence(6, 7, 8)),
        );

        /** @var array<string, int[]> $content */
        $content = [
            'a' => [1, 2, 3],
            'b' => [3, 4, 5],
            'c' => [6, 7, 8],
        ];

        $this->checkContent($multimap, $content);
    }

    public function testPut(): void
    {
        $multimap = $this->createMultiMap();
        $multimap = $multimap->put('a', 1)
                             ->put('a', 2)
                             ->put('a', 3)
                             ->put('a', 1)
                             ->put('b', 5);

        /** @var array<string, int[]> $content */
        $content = [
            'a' => [1, 2, 3],
            'b' => [5],
        ];

        $this->checkContent($multimap, $content);

        static::assertNotSame($multimap, $multimap->put('a', 7));
    }

    public function testGet(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
        );

        static::assertTrue(
            Equal::areEquals(
                new ArraySequence(1, 2, 3),
                $multimap->get('a'),
            ),
        );

        static::assertEmpty($multimap->get('b'));
    }

    public function testRemove(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
            new Pair('b', new ArraySequence(3, 4, 5)),
        );

        $multimap = $multimap->remove('a', 1)
                             ->remove('a', 3)
                             ->remove('a', 4)
                             ->remove('b', 4)
                             ->remove('c', 2);

        /** @var array<string, int[]> $content */
        $content = [
            'a' => [2],
            'b' => [3, 5],
        ];

        $this->checkContent($multimap, $content);

        static::assertNotSame($multimap, $multimap->remove('b', 4));
        static::assertSame($multimap, $multimap->remove('z', 4));
    }

    public function testRemoveAll(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
            new Pair('b', new ArraySequence(3, 4, 5)),
        );

        $multimap = $multimap->removeAll('a');

        /** @var array<string, int[]> $content */
        $content = [
            'b' => [3, 4, 5],
        ];

        $this->checkContent($multimap, $content);

        static::assertNotSame($multimap, $multimap->removeAll('b'));
        static::assertSame($multimap, $multimap->removeAll('z'));
    }

    public function testContainsKey(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
            new Pair('b', new ArraySequence(3, 4, 5)),
        );

        static::assertTrue($multimap->containsKey('a'));
        static::assertTrue($multimap->containsKey('b'));
        static::assertFalse($multimap->containsKey('c'));
    }

    public function testContainsKeyValue(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
            new Pair('b', new ArraySequence(3, 4, 5)),
        );

        static::assertTrue($multimap->containsKeyValue('a', 1));
        static::assertTrue($multimap->containsKeyValue('b', 4));
        static::assertFalse($multimap->containsKeyValue('a', 5));
        static::assertFalse($multimap->containsKeyValue('c', 1));
    }

    public function testContainsValue(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
            new Pair('b', new ArraySequence(3, 4, 5)),
        );

        static::assertTrue($multimap->containsValue(1));
        static::assertTrue($multimap->containsValue(4));
        static::assertFalse($multimap->containsValue(42));
    }

    public function testValues(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
            new Pair('b', new ArraySequence(3, 4, 5)),
        );

        static::assertSame(
            [1, 2, 3, 3, 4, 5],
            $multimap->values(),
        );
    }

    public function testKeys(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
            new Pair('b', new ArraySequence(3, 4, 5)),
        );

        static::assertSame(
            ['a', 'b'],
            $multimap->keys(),
        );
    }

    public function testPairs(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
            new Pair('b', new ArraySequence(3, 4, 5)),
        );

        /** @var array<int, Pair<string, int>> $expected */
        $expected = [
            new Pair('a', 1),
            new Pair('a', 2),
            new Pair('a', 3),
            new Pair('b', 3),
            new Pair('b', 4),
            new Pair('b', 5),
        ];

        static::assertTrue(
            Equal::areEquals($expected, $multimap->pairs()),
        );
    }

    public function testIsEmpty(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
        );

        static::assertFalse($multimap->isEmpty());

        static::assertTrue(
            $multimap->removeAll('a')
                     ->isEmpty(),
        );

        static::assertTrue(
            (new MultiValueMap(static fn () => new ArraySequence()))
                ->isEmpty(),
        );
    }

    public function testClear(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
        );

        static::assertFalse($multimap->isEmpty());
        static::assertTrue(
            $multimap->clear()
                     ->isEmpty(),
        );
    }

    public function testCount(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
            new Pair('b', new ArraySequence(3, 4, 5)),
        );

        static::assertCount(6, $multimap);
    }

    public function testGetIterator(): void
    {
        $multimap = new MultiValueMap(static fn () => new ArraySequence());

        $multimap = $multimap->put('a', 1)
                             ->put('a', 2)
                             ->put('a', 3);

        $multimap = $multimap->put('b', 1)
                             ->put('b', 2)
                             ->put('b', 3);

        $multimap = $multimap->put('x', 1)
                             ->put('x', 2)
                             ->put('x', 3);

        $order = [
            'a' => [1, 2, 3],
            'b' => [1, 2, 3],
            'x' => [1, 2, 3],
        ];

        $iterator = $multimap->getIterator();
        foreach ($order as $key => $collection) {
            foreach ($collection as $value) {
                static::assertEquals($key, $iterator->key());
                static::assertEquals($value, $iterator->current());
                $iterator->next();
            }
        }
    }

    public function testEquality(): void
    {
        $multimap1 = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
            new Pair('b', new ArraySequence(3, 4, 5)),
        );

        $multimap2 = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
            new Pair('b', new ArraySequence(3, 4, 5)),
        );

        $multimap3 = $this->createMultiMap(
            new Pair('j', new ArraySequence(7, 8, 9)),
            new Pair('k', new ArraySequence(2, 5, 6)),
        );

        static::assertTrue($multimap1->equals($multimap1));
        static::assertTrue($multimap1->equals($multimap2));
        static::assertFalse($multimap1->equals(new stdClass()));
        static::assertFalse($multimap1->equals($multimap3));
    }

    public function testHash(): void
    {
        $multimap1 = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
            new Pair('b', new ArraySequence(3, 4, 5)),
        );

        $multimap2 = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2, 3)),
            new Pair('b', new ArraySequence(3, 4, 5)),
        );

        $multimap3 = $this->createMultiMap(
            new Pair('j', new ArraySequence(7, 8, 9)),
            new Pair('k', new ArraySequence(2, 5, 6)),
        );

        static::assertSame($multimap1->hash(), $multimap1->hash());
        static::assertSame($multimap1->hash(), $multimap2->hash());
        static::assertNotSame($multimap1->hash(), $multimap3->hash());
    }

    /**
     * @param MultiMap<K, V> $multimap
     * @param array<K, V[]>  $content
     *
     * @template K as array-key
     * @template V
     */
    private function checkContent(MultiMap $multimap, array $content): void
    {
        foreach ($content as $key => $collection) {
            foreach ($collection as $value) {
                static::assertTrue($multimap->containsKeyValue($key, $value));
            }
        }
    }

    /** @return callable(): ArraySequence<int> */
    private function ofArraySequence(): callable
    {
        return static function () {
            /**
             * phpcs:ignore
             * @var ArraySequence<int>
             */
            return new ArraySequence();
        };
    }

    /**
     * @param Pair<string, Collection<int>> ...$pairs
     *
     * @return MultiMap<string, int>
     */
    private function createMultiMap(Pair ...$pairs): MultiMap
    {
        return new MultiValueMap(
            $this->ofArraySequence(),
            ...$pairs,
        );
    }
}
