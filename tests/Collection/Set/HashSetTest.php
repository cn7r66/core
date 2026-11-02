<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Collection\Set;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Collection\Set\HashSet;
use Vivarium\Equality\Equal;
use Vivarium\Equality\Equality;

#[CoversClass(HashSet::class)]
class HashSetTest extends TestCase
{
    public function testEmptyConstructor(): void
    {
        $set = new HashSet();

        static::assertCount(0, $set);
    }

    public function testAdd(): void
    {
        /** @var HashSet<int> $set */
        $set = new HashSet(1, 2, 3, 4);
        $set = $set
            ->add(1)
            ->add(1)
            ->add(2)
            ->add(5);

        $expected = [1, 2, 3, 4, 5];

        static::assertCount(5, $set);
        static::assertEquals($expected, $set->toArray());
        static::assertNotSame($set, $set->add(42));
    }

    /**
     * @param list<int> $elements
     * @param list<int> $expected
     */
    #[DataProvider('provideRemove')]
    public function testRemove(array $elements, int $element, array $expected): void
    {
        $collection = HashSet::fromArray($elements);
        $removed    = $collection->remove($element);

        static::assertSame($expected, $removed->toArray());
        static::assertFalse($removed->contains($element));
        static::assertNotSame($collection, $removed);
    }

    /** @return array<string, array{list<int>, int, list<int>}> */
    public static function provideRemove(): array
    {
        return [
            'inside' => [[1, 2, 3, 4, 5], 3, [1, 2, 4, 5]],
            'only element' => [[7], 7, []],
        ];
    }

    public function testRemoveMissing(): void
    {
        $collection = HashSet::fromArray([1, 2, 3]);

        static::assertSame($collection, $collection->remove(4));
    }

    public function testContains(): void
    {
        /** @var string[] $elements */
        $elements = ['a', 'b', 'c', 'd'];

        $set = HashSet::fromArray($elements);

        static::assertTrue($set->contains('b'));
        static::assertFalse($set->contains('z'));
    }

    public function testContainsWithObjects(): void
    {
        $stub1 = $this->createStub(Equality::class);
        $stub2 = $this->createStub(Equality::class);
        $stub3 = $this->createStub(Equality::class);

        $stub2->method('equals')
              ->willReturnMap(
                  [
                      [$stub1, false],
                      [$stub2, true],
                      [$stub3, false],
                  ],
              );

        $set = new HashSet($stub1, $stub1, $stub2, $stub3);

        static::assertTrue($set->contains($stub2));
    }

    public function testUnion(): void
    {
        /** @var HashSet<int> $set1 */
        $set1 = new HashSet(1, 3, 5, 7, 42);

        /** @var HashSet<int> $set2 */
        $set2 = new HashSet(2, 4, 6, 8, 42);

        $set = $set1->union($set2);

        $expected = [1, 3, 5, 7, 42, 2, 4, 6, 8];

        static::assertCount(9, $set);
        static::assertEquals($expected, $set->toArray());
    }

    public function testUnionImmutability(): void
    {
        /** @var HashSet<int> $set1 */
        $set1 = new HashSet(1, 2, 3);

        /** @var HashSet<int> $set2 */
        $set2 = new HashSet();

        $set = $set1->union($set2);

        static::assertNotSame($set, $set1);
    }

    public function testIntersection(): void
    {
        /** @var HashSet<int> $set1 */
        $set1 = new HashSet(1, 2, 3, 4, 5);

        /** @var HashSet<int> $set2 */
        $set2 = new HashSet(4, 5, 6, 7, 8);

        $set = $set1->intersection($set2);

        $expected = [4, 5];

        static::assertCount(2, $set);
        static::assertEquals($expected, $set->toArray());
    }

    public function testDifference(): void
    {
        /** @var HashSet<int> $set1 */
        $set1 = new HashSet(1, 2, 4, 5, 3);

        /** @var HashSet<int> $set2 */
        $set2 = new HashSet(4, 5, 6, 7, 8);

        $set = $set1->difference($set2);

        $expected = [1, 2, 3];

        static::assertCount(3, $set);
        static::assertEquals($expected, $set->toArray());
    }

    public function testIsSubsetOf(): void
    {
        /** @var HashSet<int> $set */
        $set = new HashSet(1, 2, 3, 4, 5, 6);

        /** @var HashSet<int> $subset */
        $subset = new HashSet(1, 2, 3);

        static::assertTrue($subset->isSubsetOf($set));
        static::assertFalse($set->isSubsetOf($subset));
    }

    public function testClear(): void
    {
        $set = new HashSet(1, 2, 3);
        $set = $set->clear();

        static::assertCount(0, $set);
        static::assertTrue($set->isEmpty());
    }

    public function testGetIterator(): void
    {
        /** @var array<int> $expected */
        $expected = [1, 2, 3];

        $set   = HashSet::fromArray([1, 1, 2, 2, 3, 3]);
        $index = 0;
        foreach ($set as $item) {
            static::assertEquals($expected[$index], $item);
            ++$index;
        }
    }

    public function testFromArray(): void
    {
        /** @var int[] $values */
        $values = [1, 1, 2, 2, 3, 3];

        $set1 = HashSet::fromArray($values);
        $set2 = new HashSet(1, 1, 2, 2, 3, 3);

        static::assertTrue(Equal::areEquals($set1, $set2));
    }

    public function testEquals(): void
    {
        $set1 = new HashSet(1, 2, 3, 3, 1);
        $set2 = new HashSet(1, 2, 3, 3, 1);
        $set3 = new HashSet(4, 4, 5, 2, 3);

        static::assertTrue($set1->equals($set1));
        static::assertTrue($set1->equals($set2));
        static::assertFalse($set1->equals($set3));
        static::assertFalse($set1->equals(new stdClass()));
    }

    public function testHash(): void
    {
        $set1 = new HashSet(1, 2, 3, 3, 1);
        $set2 = new HashSet(1, 2, 3, 3, 1);
        $set3 = new HashSet(4, 4, 5, 2, 3);

        static::assertSame($set1->hash(), $set1->hash());
        static::assertSame($set1->hash(), $set2->hash());
        static::assertNotSame($set1->hash(), $set3->hash());
    }

    /**
     * @param HashSet<int> $first
     * @param HashSet<int> $second
     */
    #[DataProvider('provideSameContent')]
    public function testEqualsWithSameContent(HashSet $first, HashSet $second): void
    {
        static::assertTrue($first->equals($second));
        static::assertTrue($second->equals($first));
        static::assertSame($first->hash(), $second->hash());
    }

    /** @return array<string, array{HashSet<int>, HashSet<int>}> */
    public static function provideSameContent(): array
    {
        return [
            'insertion order' => [new HashSet(1, 2, 3), new HashSet(3, 2, 1)],
            'duplicates in the input' => [new HashSet(1, 2, 3, 3, 1), new HashSet(3, 2, 1)],
        ];
    }

    /**
     * @param HashSet<int> $first
     * @param HashSet<int> $second
     */
    #[DataProvider('provideDifferentContent')]
    public function testEqualsWithDifferentContent(HashSet $first, HashSet $second): void
    {
        static::assertFalse($first->equals($second));
        static::assertFalse($second->equals($first));
    }

    /** @return array<string, array{HashSet<int>, HashSet<int>}> */
    public static function provideDifferentContent(): array
    {
        return [
            'one element differs' => [new HashSet(1, 2, 3), new HashSet(1, 2, 4)],
            'one extra element' => [new HashSet(1, 2, 3), new HashSet(1, 2, 3, 4)],
        ];
    }
}
