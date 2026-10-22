<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Collection\Map;

use OutOfBoundsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Collection\Map\HashMap;
use Vivarium\Collection\Map\Map;
use Vivarium\Collection\Pair\Pair;
use Vivarium\Collection\Set\HashSet;
use Vivarium\Equality\Equal;
use Vivarium\Equality\Equality;
use Vivarium\Test\Collection\Stub\KeyWithHashCollision;

use function count;
use function rand;

#[CoversClass(HashMap::class)]
class HashMapTest extends TestCase
{
    public function testEmptyConstructor(): void
    {
        $set = new HashSet();

        static::assertCount(0, $set);
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

        $map   = HashMap::fromKeyValue($keys, $values);
        $index = rand(0, count($keys) - 1);

        $value = $map->get($keys[$index]);

        static::assertEquals($values[$index], $value);
    }

    public function testGetWithEquality(): void
    {
        $equality = $this->createStub(Equality::class);
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
        static::expectException(OutOfBoundsException::class);
        static::expectExceptionMessage('The provided key is not present.');

        $map = new HashMap();

        /** @psalm-suppress UnusedMethodCall  */
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

    public function testContainsValue(): void
    {
        $keys   = [1, 2, 3, 4];
        $values = ['a', 'b', 'c', 'd'];

        $map   = HashMap::fromKeyValue($keys, $values);
        $index = rand(0, count($values) - 1);

        static::assertTrue($map->containsValue($values[$index]));
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
}
