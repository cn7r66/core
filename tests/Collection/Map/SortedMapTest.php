<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Collection\Map;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Collection\Exception\KeyNotFound;
use Vivarium\Collection\Map\Map;
use Vivarium\Collection\Map\SortedMap;
use Vivarium\Comparator\ByByte;
use Vivarium\Comparator\ByNumber;

use function array_combine;
use function array_keys;
use function array_values;
use function count;
use function usort;

#[CoversClass(SortedMap::class)]
class SortedMapTest extends TestCase
{
    public function testKeyOrder(): void
    {
        /** @var string[] $keys */
        $keys   = ['c', 'b', 'a', 'd'];
        $values = [1, 2, 3, 4];

        $map = SortedMap::fromKeyValue(new ByByte(), $keys, $values);

        $expected = ['a', 'b', 'c', 'd'];
        $keys     = $map->keys();

        static::assertEquals($expected, $keys);
    }

    public function testPut(): void
    {
        /** @var string[] $keys */
        $keys   = ['c', 'b', 'a', 'd'];
        $values = [1, 2, 3, 4];

        /** @var Map<string, int> $map */
        $map = SortedMap::fromKeyValue(new ByByte(), $keys, $values);

        $map = $map->put('f', 42);
        $map = $map->put('a', 5);

        static::assertCount(5, $map);
        static::assertEquals(5, $map->get('a'));
    }

    public function testDoublePut(): void
    {
        $map = new SortedMap(new ByByte());
        $map = $map
            ->put('c', 1)
            ->put('c', 12)
            ->put('a', 7)
            ->put('b', 2);

        $keys   = ['a', 'b', 'c'];
        $values = [7, 2, 12];

        static::assertCount(3, $map);
        static::assertEquals($keys, $map->keys());
        static::assertEquals($values, $map->values());
        static::assertNotSame($map, $map->put('z', 42));
    }

    public function testKeySorting(): void
    {
        $map = new SortedMap(new ByByte());
        $map = $map
            ->put('Hello', 1)
            ->put('H', 1)
            ->put('Ab', 1)
            ->put('A', 1);

        $keys = ['A', 'Ab', 'H', 'Hello'];
        static::assertEquals($keys, $map->keys());
    }

    public function testGet(): void
    {
        /** @var int[] $keys */
        $keys   = [0, 1];
        $values = ['a', 'b'];

        /** @var Map<int, string> $map */
        $map = SortedMap::fromKeyValue(new ByNumber(), $keys, $values);

        static::assertEquals('a', $map->get(0));
        static::assertEquals('b', $map->get(1));
    }

    public function testGetNotFound(): void
    {
        static::expectException(KeyNotFound::class);
        static::expectExceptionMessage('Expected a key present in the map. Got int 1.');

        $map = new SortedMap(new ByNumber());

        $map->get(1);
    }

    public function testRemove(): void
    {
        /** @var int[] $keys */
        $keys   = [5, 3, 2, 1, 7];
        $values = [7, 5, 3, 8, 9];

        /** @var Map<int, int> $map */
        $map = SortedMap::fromKeyValue(new ByNumber(), $keys, $values);

        static::assertTrue($map->containsKey(2));

        $map = $map->remove(2);

        static::assertFalse($map->containsKey(2));
        static::assertNotSame($map, $map->remove(1));
    }

    /** @param list<string> $values */
    #[DataProvider('provideFromKeyValueMismatch')]
    public function testFromKeyValueRejectsMismatchedCounts(array $values, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        SortedMap::fromKeyValue(new ByNumber(), [1, 2, 3], $values);
    }

    #[DataProvider('provideRemoveMissing')]
    public function testRemoveMissing(int $key): void
    {
        $map = SortedMap::fromKeyValue(new ByNumber(), [2, 4, 6], ['a', 'b', 'c']);

        static::assertSame($map, $map->remove($key));
    }

    public function testRemoveOnFirstElement(): void
    {
        $map = new SortedMap(new ByNumber());
        $map = $map
            ->put(0, 1)
            ->put(1, 1);

        static::assertTrue($map->containsKey(0));
        static::assertTrue($map->containsKey(1));
        static::assertFalse($map->containsKey(2));

        $map = $map->remove(0);

        static::assertFalse($map->containsKey(0));
        static::assertFalse($map->containsKey(2));
        static::assertTrue($map->containsKey(1));
    }

    public function testPairs(): void
    {
        /** @var int[] $keys */
        $keys   = [1, 2, 3, 4, 5];
        $values = ['a', 'b', 'c', 'd', 'e'];

        /** @var Map<int, string> $map */
        $map = SortedMap::fromKeyValue(new ByNumber(), $keys, $values);

        usort($keys, (new ByNumber())->compare(...));

        $pairs = $map->pairs();
        for ($i = 0; $i < count($keys); $i++) {
            static::assertEquals($keys[$i], $pairs[$i]->getKey());
        }
    }

    public function testClear(): void
    {
        /** @var int[] $keys */
        $keys   = [1, 2, 3, 4, 5];
        $values = ['a', 'b', 'c', 'd', 'e'];

        /** @var Map<int, string> $map */
        $map     = SortedMap::fromKeyValue(new ByNumber(), $keys, $values);
        $cleared = $map->clear();

        static::assertCount(0, $cleared);
        static::assertCount(5, $map);
    }

    public function testGetIterator(): void
    {
        /** @var string[] $keys */
        $keys   = ['a', 'b', 'c'];
        $values = [1, 2, 3];

        /** @var Map<string, int> $map */
        $map = SortedMap::fromKeyValue(new ByByte(), $keys, $values);

        $order = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ];

        $iterator = $map->getIterator();
        foreach ($order as $key => $value) {
            static::assertEquals($key, $iterator->key());
            static::assertEquals($value, $iterator->current());
            $iterator->next();
        }
    }

    public function testContainsValue(): void
    {
        /** @var int[] $keys */
        $keys   = [1, 2, 3, 4, 5];
        $values = ['a', 'b', 'c', 'd', 'e'];

        $map = SortedMap::fromKeyValue(new ByNumber(), $keys, $values);

        static::assertTrue($map->containsValue('c'));
        static::assertFalse($map->containsValue('z'));
    }

    public function testValues(): void
    {
        /** @var int[] $keys */
        $keys   = [1, 2];
        $values = ['a', 'b'];

        $map = SortedMap::fromKeyValue(new ByNumber(), $keys, $values);

        static::assertSame(['a', 'b'], $map->values());
    }

    public function testIsEmpty(): void
    {
        $map = new SortedMap(new ByNumber());

        static::assertTrue($map->isEmpty());
    }

    public function testEquality(): void
    {
        /** @var int[] $keys */
        $keys   = [1, 2, 3];
        $values = ['a', 'b', 'c'];

        $map1 = SortedMap::fromKeyValue(new ByNumber(), $keys, $values);
        $map2 = SortedMap::fromKeyValue(new ByNumber(), $keys, $values);

        /** @var int[] $keys */
        $keys   = [3, 4, 5];
        $values = ['d', 'e', 'f'];

        $map3 = SortedMap::fromKeyValue(new ByNumber(), $keys, $values);

        static::assertTrue($map1->equals($map1));
        static::assertTrue($map1->equals($map2));
        static::assertFalse($map1->equals(new stdClass()));
        static::assertFalse($map1->equals($map3));
    }

    public function testHash(): void
    {
        /** @var int[] $keys */
        $keys   = [1, 2, 3];
        $values = ['a', 'b', 'c'];

        $map1 = SortedMap::fromKeyValue(new ByNumber(), $keys, $values);
        $map2 = SortedMap::fromKeyValue(new ByNumber(), $keys, $values);

        /** @var int[] $keys */
        $keys   = [3, 4, 5];
        $values = ['d', 'e', 'f'];

        $map3 = SortedMap::fromKeyValue(new ByNumber(), $keys, $values);

        static::assertSame($map1->hash(), $map1->hash());
        static::assertSame($map1->hash(), $map2->hash());
        static::assertNotSame($map1->hash(), $map3->hash());
    }

    /**
     * @param array<string, int> $pairs
     * @param array<string, int> $expected
     */
    #[DataProvider('provideFilter')]
    public function testFilter(array $pairs, array $expected): void
    {
        $map      = $this->mapOf($pairs);
        $filtered = $map->filter(static fn (int $value, string $key): bool => $value > 1 && $key !== 'b');

        static::assertSame($expected, $this->contentOf($filtered));
        static::assertSame(['0', ...array_keys($expected)], $filtered->put('0', 0)->keys());
        static::assertCount(count($pairs), $map);
        static::assertNotSame($map, $filtered);
    }

    /**
     * @param array<string, int>    $pairs
     * @param array<string, string> $expected
     */
    #[DataProvider('provideMap')]
    public function testMap(array $pairs, array $expected): void
    {
        $mapped = $this->mapOf($pairs)->map(static fn (int $value, string $key): string => $key . $value);

        static::assertSame($expected, $this->contentOf($mapped));
        static::assertSame(['0', ...array_keys($expected)], $mapped->put('0', '0')->keys());
    }

    /** @param array<string, int> $pairs */
    #[DataProvider('provideReduce')]
    public function testReduce(array $pairs, string $expected): void
    {
        static::assertSame($expected, $this->mapOf($pairs)->reduce(
            static fn (string $carry, int $value, string $key): string => $carry . $key . $value,
            '>',
        ));
    }

    /** @return array<string, array{list<string>, string}> */
    public static function provideFromKeyValueMismatch(): array
    {
        return [
            'too few values' => [['a'], 'Expected 3 values, one per key. Got int 1.'],
            'too many values' => [['a', 'b', 'c', 'd'], 'Expected 3 values, one per key. Got int 4.'],
        ];
    }

    /** @return array<string, array{int}> */
    public static function provideRemoveMissing(): array
    {
        return [
            'before every key' => [1],
            'between keys' => [3],
            'after every key' => [7],
        ];
    }

    /** @return array<string, array{array<string, int>, array<string, int>}> */
    public static function provideFilter(): array
    {
        return [
            'some kept' => [['a' => 2, 'b' => 3, 'c' => 1, 'd' => 4], ['a' => 2, 'd' => 4]],
            'none kept' => [['b' => 5, 'c' => 0], []],
            'all kept' => [['a' => 2, 'c' => 3], ['a' => 2, 'c' => 3]],
            'empty' => [[], []],
        ];
    }

    /** @return array<string, array{array<string, int>, array<string, string>}> */
    public static function provideMap(): array
    {
        return [
            'pairs' => [['b' => 2, 'a' => 1], ['a' => 'a1', 'b' => 'b2']],
            'empty' => [[], []],
        ];
    }

    /** @return array<string, array{array<string, int>, string}> */
    public static function provideReduce(): array
    {
        return [
            'in iteration order' => [['c' => 3, 'a' => 1, 'b' => 2], '>a1b2c3'],
            'empty' => [[], '>'],
        ];
    }

    /**
     * @param array<string, V> $pairs
     *
     * @return SortedMap<string, V>
     *
     * @template V
     */
    private function mapOf(array $pairs): SortedMap
    {
        return SortedMap::fromKeyValue(new ByByte(), array_keys($pairs), array_values($pairs));
    }

    /**
     * @param SortedMap<string, V> $map
     *
     * @return array<string, V>
     *
     * @template V
     */
    private function contentOf(SortedMap $map): array
    {
        return array_combine($map->keys(), $map->values());
    }
}
