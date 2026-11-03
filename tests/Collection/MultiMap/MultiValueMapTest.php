<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Collection\MultiMap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Collection\Collection;
use Vivarium\Collection\MultiMap\MultiMap;
use Vivarium\Collection\MultiMap\MultiValueMap;
use Vivarium\Collection\Pair\Pair;
use Vivarium\Collection\Sequence\ArraySequence;
use Vivarium\Collection\Set\HashSet;
use Vivarium\Equality\Equal;

use function array_keys;

use function array_map;
use function intdiv;
use function sort;

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

    public function testConstructorSkipsEmptyCollections(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence()),
            new Pair('b', new ArraySequence(1)),
        );

        $this->checkContent($multimap, ['b' => [1]]);
        static::assertFalse($multimap->containsKey('a'));
    }

    public function testConstructorMergesDuplicateKeys(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence(1, 2)),
            new Pair('b', new ArraySequence(9)),
            new Pair('a', new ArraySequence(3, 4)),
        );

        $this->checkContent($multimap, ['a' => [1, 2, 3, 4], 'b' => [9]]);
    }

    public function testRemoveLastValueDropsKey(): void
    {
        $multimap = $this->createMultiMap(
            new Pair('a', new ArraySequence(1)),
            new Pair('b', new ArraySequence(2)),
        );

        $multimap = $multimap->remove('a', 1);

        static::assertFalse($multimap->containsKey('a'));
        $this->checkContent($multimap, ['b' => [2]]);

        $multimap = $multimap->remove('b', 2);

        static::assertTrue($multimap->isEmpty());
        static::assertSame([], $multimap->keys());
        static::assertCount(0, $multimap);
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
            'a' => [1, 2, 3, 1],
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

        static::assertSame($multimap, $multimap->remove('b', 4));
        static::assertSame($multimap, $multimap->remove('z', 4));
    }

    public function testRemoveEveryOccurrence(): void
    {
        $multimap = $this->createMultiMap(new Pair('a', new ArraySequence(1, 2, 1)));

        $multimap = $multimap->remove('a', 1);

        static::assertSame([2], $multimap->get('a')->toArray());
        static::assertFalse($multimap->containsKeyValue('a', 1));
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
     * @param MultiValueMap<string, int> $first
     * @param MultiValueMap<string, int> $second
     */
    #[DataProvider('provideSameContent')]
    public function testEqualsWithSameContent(MultiValueMap $first, MultiValueMap $second): void
    {
        static::assertTrue($first->equals($second));
        static::assertTrue($second->equals($first));
        static::assertSame($first->hash(), $second->hash());
    }

    /**
     * @param MultiValueMap<string, int> $first
     * @param MultiValueMap<string, int> $second
     */
    #[DataProvider('provideDifferentContent')]
    public function testEqualsWithDifferentContent(MultiValueMap $first, MultiValueMap $second): void
    {
        static::assertFalse($first->equals($second));
        static::assertFalse($second->equals($first));
    }

    /**
     * @param array<string, list<int>> $content
     * @param array<string, list<int>> $expected
     */
    #[DataProvider('provideFilter')]
    public function testFilter(array $content, array $expected): void
    {
        $multimap = $this->multimapOf($content);
        $filtered = $multimap->filter(static fn (int $value, string $key): bool => $value > 1 && $key !== 'b');

        $this->checkContent($filtered, $expected);
        $this->checkContent($multimap, $content);
    }

    /**
     * @param array<string, list<int>>              $content
     * @param array<string, list<non-empty-string>> $expected
     */
    #[DataProvider('provideMap')]
    public function testMap(array $content, array $expected): void
    {
        $mapped = $this->multimapOf($content)->map(
            static fn (int $value, string $key): string => $key . $value,
            static fn (): ArraySequence => new ArraySequence(),
        );

        $this->checkContent($mapped, $expected);
    }

    public function testMapBucketsFollowTheFactory(): void
    {
        $multimap = new MultiValueMap(
            static fn (): ArraySequence => new ArraySequence(),
            new Pair('a', new ArraySequence(3, 1)),
        );
        $mapped   = $multimap->map(
            static fn (int $value): int => $value * 10,
            static fn (): HashSet => new HashSet(),
        );

        static::assertInstanceOf(HashSet::class, $mapped->get('a'));
        static::assertInstanceOf(HashSet::class, $mapped->get('b'));
        static::assertInstanceOf(HashSet::class, $mapped->put('b', 5)->get('b'));
    }

    public function testMapMergesEqualResultsInSets(): void
    {
        $multimap = new MultiValueMap(
            static fn (): HashSet => new HashSet(),
            new Pair('a', new HashSet(1, 2, 3, 4)),
        );
        $mapped   = $multimap->map(
            static fn (int $value): int => intdiv($value, 2),
            static fn (): HashSet => new HashSet(),
        );

        static::assertCount(3, $mapped);
    }

    /**
     * @param array<string, list<int>> $content
     * @param list<string>             $expected
     */
    #[DataProvider('provideReduce')]
    public function testReduce(array $content, array $expected): void
    {
        $visited = $this->multimapOf($content)->reduce(
            static fn (array $carry, int $value, string $key): array => [...$carry, $key . $value],
            [],
        );
        sort($visited);

        static::assertSame($expected, $visited);
    }

    /** @return array<string, array{MultiValueMap<string, int>, MultiValueMap<string, int>}> */
    public static function provideSameContent(): array
    {
        $factory = static fn (): ArraySequence => new ArraySequence();

        return [
            'key order' => [
                new MultiValueMap(
                    $factory,
                    new Pair('a', new ArraySequence(1, 2)),
                    new Pair('b', new ArraySequence(3)),
                ),
                new MultiValueMap(
                    $factory,
                    new Pair('b', new ArraySequence(3)),
                    new Pair('a', new ArraySequence(1, 2)),
                ),
            ],
        ];
    }

    /** @return array<string, array{MultiValueMap<string, int>, MultiValueMap<string, int>}> */
    public static function provideDifferentContent(): array
    {
        $factory = static fn (): ArraySequence => new ArraySequence();

        return [
            'values of a key in another order' => [
                new MultiValueMap($factory, new Pair('a', new ArraySequence(1, 2))),
                new MultiValueMap($factory, new Pair('a', new ArraySequence(2, 1))),
            ],
        ];
    }

    /** @return array<string, array{array<string, list<int>>, array<string, list<int>>}> */
    public static function provideFilter(): array
    {
        return [
            'some kept' => [
                ['a' => [1, 2, 3], 'b' => [4], 'c' => [0, 5]],
                ['a' => [2, 3], 'c' => [5]],
            ],
            'emptied bucket dropped' => [['a' => [1], 'c' => [2]], ['c' => [2]]],
            'none kept' => [['a' => [0, 1], 'b' => [5]], []],
            'all kept' => [['a' => [2, 3]], ['a' => [2, 3]]],
            'empty' => [[], []],
        ];
    }

    /** @return array<string, array{array<string, list<int>>, array<string, list<non-empty-string>>}> */
    public static function provideMap(): array
    {
        return [
            'values' => [['a' => [1, 2], 'b' => [3]], ['a' => ['a1', 'a2'], 'b' => ['b3']]],
            'empty' => [[], []],
        ];
    }

    /** @return array<string, array{array<string, list<int>>, list<string>}> */
    public static function provideReduce(): array
    {
        return [
            'every value' => [['b' => [3], 'a' => [1, 2]], ['a1', 'a2', 'b3']],
            'empty' => [[], []],
        ];
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
        $keys = $multimap->keys();
        sort($keys);
        $expected = array_keys($content);
        sort($expected);

        static::assertSame($expected, $keys);
        foreach ($content as $key => $values) {
            static::assertSame($values, $multimap->get($key)->toArray());
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

    /**
     * @param array<string, list<int>> $content
     *
     * @return MultiMap<string, int>
     */
    private function multimapOf(array $content): MultiMap
    {
        return $this->createMultiMap(...array_map(
            static fn (string $key, array $values): Pair => new Pair($key, ArraySequence::fromArray($values)),
            array_keys($content),
            $content,
        ));
    }
}
