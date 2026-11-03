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
use Vivarium\Collection\Map\HashMap;
use Vivarium\Collection\Map\Map;
use Vivarium\Collection\Pair\Pair;
use Vivarium\Equality\Equal;
use Vivarium\Equality\Equality;
use Vivarium\Test\Collection\Stub\KeyWithHashCollision;

use function array_keys;
use function array_values;
use function count;
use function sort;

#[CoversClass(HashMap::class)]
class HashMapTest extends TestCase
{
    public function testEmptyConstructor(): void
    {
        $map = new HashMap();

        static::assertCount(0, $map);
    }

    public function testConstructionWithPairs(): void
    {
        $map = new HashMap(
            new Pair('a', 1),
            new Pair('b', 2),
            new Pair('c', 3),
        );

        static::assertCount(3, $map);
    }

    public function testPut(): void
    {
        /** @var Map<int, string> $map */
        $map = new HashMap(
            new Pair(1, 'a'),
            new Pair(2, 'b'),
            new Pair(3, 'c'),
        );

        $map = $map->put(4, 'd');
        $map = $map->put(1, 'z');

        static::assertCount(4, $map);
        static::assertEquals('z', $map->get(1));
        static::assertNotSame($map, $map->put(5, 'e'));
    }

    public function testPutHashCollision(): void
    {
        /** @var Map<KeyWithHashCollision, string> $map */
        $map = new HashMap(
            new Pair(new KeyWithHashCollision(1), 'a'),
            new Pair(new KeyWithHashCollision(1), 'b'),
            new Pair(new KeyWithHashCollision(2), 'c'),
        );

        $map = $map->put(new KeyWithHashCollision(2), 'd');
        $map = $map->put(new KeyWithHashCollision(3), 'z');

        static::assertCount(3, $map);
        static::assertEquals('z', $map->get(new KeyWithHashCollision(3)));
    }

    public function testGet(): void
    {
        $keys   = [1, 2, 3, 4];
        $values = ['a', 'b', 'c', 'd'];

        $map = HashMap::fromKeyValue($keys, $values);

        foreach ($keys as $index => $key) {
            static::assertSame($values[$index], $map->get($key));
        }
    }

    public function testGetWithEquality(): void
    {
        $equality = static::createStub(Equality::class);
        $equality->method('hash')
                 ->willReturn('a4bbe8f088dbfba72434f05585ed0ce4ecb3c952');

        $equality->method('equals')
                 ->willReturnMap([
                     [$equality, true],
                 ]);

        $map   = new HashMap(new Pair($equality, 1));
        $value = $map->get($equality);

        static::assertEquals(1, $value);
    }

    public function testGetException(): void
    {
        static::expectException(KeyNotFound::class);
        static::expectExceptionMessage('Expected a key present in the map. Got object stdClass.');

        $map = new HashMap();

        $map->get(new stdClass());
    }

    public function testRemove(): void
    {
        $keys   = [1, 2, 3, 4];
        $values = ['a', 'b', 'c', 'd'];

        $map = HashMap::fromKeyValue($keys, $values);
        $map = $map->remove(1)
                   ->remove(2);

        static::assertCount(2, $map);
        static::assertFalse($map->containsKey(1));
        static::assertFalse($map->containsKey(2));
        static::assertNotSame($map, $map->remove(3));
        static::assertSame($map, $map->remove(42));
    }

    public function testRemoveHashCollision(): void
    {
        /** @var Map<KeyWithHashCollision, string> $map */
        $map = new HashMap(
            new Pair(new KeyWithHashCollision(1), 'a'),
            new Pair(new KeyWithHashCollision(1), 'b'),
            new Pair(new KeyWithHashCollision(2), 'c'),
            new Pair(new KeyWithHashCollision(2), 'd'),
            new Pair(new KeyWithHashCollision(3), 'z'),
        );

        $map = $map->remove(new KeyWithHashCollision(1))
                   ->remove(new KeyWithHashCollision(2));

        static::assertCount(1, $map);
    }

    /** @param list<string> $expected */
    #[DataProvider('provideRemoveHashCollisionIteration')]
    public function testRemoveHashCollisionIteration(int $removed, array $expected): void
    {
        $map = new HashMap(
            new Pair(new KeyWithHashCollision(1), 'a'),
            new Pair(new KeyWithHashCollision(2), 'b'),
            new Pair(new KeyWithHashCollision(3), 'c'),
            new Pair(new KeyWithHashCollision(99), 'z'),
        );

        $map = $map->remove(new KeyWithHashCollision($removed));

        $iterated = [];
        foreach ($map as $key => $value) {
            static::assertSame($map->get($key), $value);
            $iterated[] = $value;
        }

        sort($iterated);
        static::assertSame($expected, $iterated);
    }

    public function testContainsValue(): void
    {
        $keys   = [1, 2, 3, 4];
        $values = ['a', 'b', 'c', 'd'];

        $map = HashMap::fromKeyValue($keys, $values);

        foreach ($values as $value) {
            static::assertTrue($map->containsValue($value));
        }

        static::assertFalse($map->containsValue(-42));
    }

    public function testValues(): void
    {
        $keys   = [1, 2, 3, 4];
        $values = ['a', 'b', 'c', 'd'];

        $map  = HashMap::fromKeyValue($keys, $values);
        $vals = $map->values();

        static::assertEquals($values, $vals);
    }

    public function testKeys(): void
    {
        $keys   = [1, 2, 3, 4];
        $values = ['a', 'b', 'c', 'd'];

        $map  = HashMap::fromKeyValue($keys, $values);
        $keyz = $map->keys();

        static::assertEquals($keys, $keyz);
    }

    public function testPairs(): void
    {
        /** @var array<int> $keys */
        $keys = [1, 2, 3, 4];
        /** @var array<string> $values */
        $values = ['a', 'b', 'c', 'd'];

        $map = HashMap::fromKeyValue($keys, $values);

        $pairs = $map->pairs();
        for ($i = 0; $i < count($keys); $i++) {
            static::assertEquals($keys[$i], $pairs[$i]->getKey());
            static::assertEquals($values[$i], $pairs[$i]->getValue());
        }
    }

    public function testClear(): void
    {
        $keys   = [1, 2, 3, 4];
        $values = ['a', 'b', 'c', 'd'];

        $map = HashMap::fromKeyValue($keys, $values);
        $map = $map->clear();

        static::assertEquals(0, $map->count());
        static::assertTrue($map->isEmpty());
    }

    public function testGetIterator(): void
    {
        $map = new HashMap(
            new Pair('a', 1),
            new Pair('b', 2),
            new Pair('c', 3),
        );

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

    /** @param list<string> $values */
    #[DataProvider('provideFromKeyValueMismatch')]
    public function testFromKeyValueRejectsMismatchedCounts(array $values, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        HashMap::fromKeyValue([1, 2, 3], $values);
    }

    public function testFromKeyValue(): void
    {
        $keys   = [1, 2, 3];
        $values = ['a', 'b', 'c'];

        $map1 = HashMap::fromKeyValue($keys, $values);

        $map2 = new HashMap(
            new Pair(1, 'a'),
            new Pair(2, 'b'),
            new Pair(3, 'c'),
        );

        static::assertTrue(Equal::areEquals($map1, $map2));
    }

    public function testEquality(): void
    {
        $keys   = [1, 2, 3];
        $values = ['a', 'b', 'c'];

        $map1 = HashMap::fromKeyValue($keys, $values);
        $map2 = HashMap::fromKeyValue($keys, $values);

        $keys   = [3, 4, 5];
        $values = ['d', 'e', 'f'];

        $map3 = HashMap::fromKeyValue($keys, $values);

        static::assertTrue($map1->equals($map1));
        static::assertTrue($map1->equals($map2));
        static::assertFalse($map1->equals(new stdClass()));
        static::assertFalse($map1->equals($map3));
    }

    public function testHash(): void
    {
        $keys   = [1, 2, 3];
        $values = ['a', 'b', 'c'];

        $map1 = HashMap::fromKeyValue($keys, $values);
        $map2 = HashMap::fromKeyValue($keys, $values);

        $keys   = [3, 4, 5];
        $values = ['d', 'e', 'f'];

        $map3 = HashMap::fromKeyValue($keys, $values);

        static::assertSame($map1->hash(), $map1->hash());
        static::assertSame($map1->hash(), $map2->hash());
        static::assertNotSame($map1->hash(), $map3->hash());
    }

    /**
     * @param HashMap<mixed, mixed> $first
     * @param HashMap<mixed, mixed> $second
     */
    #[DataProvider('provideSameContent')]
    public function testEqualsWithSameContent(HashMap $first, HashMap $second): void
    {
        static::assertTrue($first->equals($second));
        static::assertTrue($second->equals($first));
        static::assertSame($first->hash(), $second->hash());
    }

    /**
     * @param HashMap<mixed, mixed> $first
     * @param HashMap<mixed, mixed> $second
     */
    #[DataProvider('provideDifferentContent')]
    public function testEqualsWithDifferentContent(HashMap $first, HashMap $second): void
    {
        static::assertFalse($first->equals($second));
        static::assertFalse($second->equals($first));
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

        static::assertTrue($this->mapOf($expected)->equals($filtered));
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

        static::assertTrue($this->mapOf($expected)->equals($mapped));
    }

    /**
     * @param array<string, int> $pairs
     * @param list<string>       $expected
     */
    #[DataProvider('provideReduce')]
    public function testReduce(array $pairs, array $expected): void
    {
        $visited = $this->mapOf($pairs)->reduce(
            static fn (array $carry, int $value, string $key): array => [...$carry, $key . $value],
            [],
        );
        sort($visited);

        static::assertSame($expected, $visited);
    }

    /** @return array<string, array{int, list<string>}> */
    public static function provideRemoveHashCollisionIteration(): array
    {
        return [
            'first of the bucket' => [1, ['b', 'c', 'z']],
            'middle of the bucket' => [2, ['a', 'c', 'z']],
            'last of the bucket' => [3, ['a', 'b', 'z']],
        ];
    }

    /** @return array<string, array{list<string>, string}> */
    public static function provideFromKeyValueMismatch(): array
    {
        return [
            'too few values' => [['a'], 'Expected 3 values, one per key. Got int 1.'],
            'too many values' => [['a', 'b', 'c', 'd'], 'Expected 3 values, one per key. Got int 4.'],
        ];
    }

    /** @return array<string, array{object, object}> */
    public static function provideSameContent(): array
    {
        return [
            'insertion order' => [
                HashMap::fromKeyValue([1, 2, 3], ['a', 'b', 'c']),
                HashMap::fromKeyValue([3, 2, 1], ['c', 'b', 'a']),
            ],
            'colliding keys in another order' => [
                new HashMap(
                    new Pair(new KeyWithHashCollision(1), 'a'),
                    new Pair(new KeyWithHashCollision(2), 'b'),
                    new Pair(new KeyWithHashCollision(99), 'z'),
                ),
                new HashMap(
                    new Pair(new KeyWithHashCollision(99), 'z'),
                    new Pair(new KeyWithHashCollision(2), 'b'),
                    new Pair(new KeyWithHashCollision(1), 'a'),
                ),
            ],
            'removed and put back' => [
                HashMap::fromKeyValue([1, 2, 3], ['a', 'b', 'c']),
                HashMap::fromKeyValue([1, 2, 3], ['a', 'b', 'c'])->remove(1)->put(1, 'a'),
            ],
            'empty' => [new HashMap(), new HashMap()],
        ];
    }

    /** @return array<string, array{object, object}> */
    public static function provideDifferentContent(): array
    {
        return [
            'one value differs' => [
                HashMap::fromKeyValue([1, 2, 3], ['a', 'b', 'c']),
                HashMap::fromKeyValue([1, 2, 3], ['a', 'b', 'x']),
            ],
            'one extra key' => [
                HashMap::fromKeyValue([1, 2, 3], ['a', 'b', 'c']),
                HashMap::fromKeyValue([1, 2, 3, 4], ['a', 'b', 'c', 'd']),
            ],
            'values swapped between keys' => [
                HashMap::fromKeyValue([1, 2], ['a', 'b']),
                HashMap::fromKeyValue([1, 2], ['b', 'a']),
            ],
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
            'pairs' => [['a' => 1, 'b' => 2], ['a' => 'a1', 'b' => 'b2']],
            'empty' => [[], []],
        ];
    }

    /** @return array<string, array{array<string, int>, list<string>}> */
    public static function provideReduce(): array
    {
        return [
            'pairs' => [['b' => 2, 'a' => 1], ['a1', 'b2']],
            'empty' => [[], []],
        ];
    }

    /**
     * @param array<string, V> $pairs
     *
     * @return HashMap<string, V>
     *
     * @template V
     */
    private function mapOf(array $pairs): HashMap
    {
        return HashMap::fromKeyValue(array_keys($pairs), array_values($pairs));
    }
}
