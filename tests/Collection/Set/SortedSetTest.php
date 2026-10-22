<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Collection\Set;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Collection\Set\SortedSet;
use Vivarium\Comparator\Comparator;
use Vivarium\Comparator\IntegerComparator;
use Vivarium\Comparator\StringComparator;
use Vivarium\Equality\Equal;
use Vivarium\Equality\Equality;

#[CoversClass(SortedSet::class)]
class SortedSetTest extends TestCase
{
    public function testAdd(): void
    {
        /** @var int[] $values */
        $values = [5, 2, 4, 3, 1];

        $set = new SortedSet(new IntegerComparator(), ...$values);
        $set = $set
            ->add(3)
            ->add(6);

        $expected = [1, 2, 3, 4, 5, 6];

        static::assertCount(6, $set);
        static::assertEquals($expected, $set->toArray());
        static::assertNotSame($set, $set->add(42));
    }

    public function testRemove(): void
    {
        /** @var int[] $values */
        $values = [5, 2, 3, 1, 7];

        $set = new SortedSet(new IntegerComparator(), ...$values);
        $set = $set->remove(3);

        $expected = [1, 2, 5, 7];

        static::assertCount(4, $set);
        static::assertEquals($expected, $set->toArray());
        static::assertNotSame($set, $set->remove(7));
    }

    public function testClear(): void
    {
        /** @var int[] $values */
        $values = [3, 2, 1];

        $set = new SortedSet(new IntegerComparator(), ...$values);
        $set = $set->clear();

        static::assertCount(0, $set);
        static::assertTrue($set->isEmpty());
    }

    public function testGetIterator(): void
    {
        /** @var array<int> $values */
        $values = [3, 2, 1, 3, 1, 3];
        /** @var array<int> $expected */
        $expected = [1, 2, 3];

        $set   = SortedSet::fromArray(new IntegerComparator(), $values);
        $index = 0;
        foreach ($set as $item) {
            static::assertEquals($expected[$index], $item);
            ++$index;
        }
    }

    public function testContains(): void
    {
        /** @var string[] $values */
        $values = ['c', 'd', 'e', 'a'];

        $set = new SortedSet(new StringComparator(), ...$values);

        static::assertTrue($set->contains('d'));
        static::assertFalse($set->contains('z'));
    }

    public function testContainsWithObjects(): void
    {
        $stub1 = static::createStub(Equality::class);
        $stub2 = static::createStub(Equality::class);
        $stub3 = static::createStub(Equality::class);

        $stub2
            ->method('equals')
            ->willReturnMap([
                [$stub1, false],
                [$stub2, true],
                [$stub3, false]
            ]);

        $comparator = static::createStub(Comparator::class);

        $comparator
            ->method('compare')
            ->willReturnMap([
                [$stub1, $stub1, 0],
                [$stub1, $stub2, -1],
                [$stub1, $stub3, -1],
                [$stub2, $stub1, 1],
                [$stub2, $stub2, 0],
                [$stub2, $stub3, -1],
                [$stub3, $stub1, 1],
                [$stub3, $stub2, 1],
                [$stub3, $stub3, 0],
            ]);
            
        $set = new SortedSet($comparator, $stub1, $stub2, $stub2, $stub3);

        static::assertTrue($set->contains($stub1));
    }

    public function testUnion(): void
    {
        /** @var int[] $values1 */
        $values1 = [5, 3, 2, 42, 7];

        /** @var int[] $values2 */
        $values2 = [6, 27, 1, 3, 7];

        $set1 = new SortedSet(new IntegerComparator(), ...$values1);
        $set2 = new SortedSet(new IntegerComparator(), ...$values2);

        $set = $set1->union($set2);

        $expected = [1, 2, 3, 5, 6, 7, 27, 42];

        static::assertCount(8, $set);
        static::assertEquals($expected, $set->toArray());
    }

    public function testIntersection(): void
    {
        /** @var int[] $values1 */
        $values1 = [5, 4, 2, 3, 6];

        $set1 = new SortedSet(new IntegerComparator(), ...$values1);

        /** @var int[] $values2 */
        $values2 = [8, 7, 4, 5, 6, 1];

        $set2 = new SortedSet(new IntegerComparator(), ...$values2);

        $set = $set1->intersection($set2);

        $expected = [4, 5, 6];

        static::assertCount(3, $set);
        static::assertEquals($expected, $set->toArray());
    }

    public function testDifference(): void
    {
        /** @var int[] $values1 */
        $values1 = [4, 5, 1, 2, 3];

        $set1 = new SortedSet(new IntegerComparator(), ...$values1);

        /** @var int[] $values2 */
        $values2 = [8, 7, 4, 6, 5, 2];

        $set2 = new SortedSet(new IntegerComparator(), ...$values2);

        $set = $set1->difference($set2);

        $expected = [1, 3];

        static::assertCount(2, $set);
        static::assertEquals($expected, $set->toArray());
    }

    public function testIsSubsetOf(): void
    {
        /** @var int[] $values1 */
        $values1 = [4, 5, 1, 2, 6, 3];

        $set = new SortedSet(new IntegerComparator(), ...$values1);

        /** @var int[] $values2 */
        $values2 = [3, 2, 1];

        $subset = new SortedSet(new IntegerComparator(), ...$values2);

        static::assertTrue($subset->isSubsetOf($set));
        static::assertFalse($set->isSubsetOf($subset));
    }

    public function testUnionImmutability(): void
    {
        /** @var int[] $values */
        $values = [1, 2, 3];

        $set1 = new SortedSet(new IntegerComparator(), ...$values);
        $set2 = new SortedSet(new IntegerComparator());

        $set = $set1->union($set2);

        static::assertNotSame($set, $set1);
    }

    public function testFromArray(): void
    {
        /** @var int[] $values */
        $values = [3, 3, 2, 2, 1, 1];

        $set1 = SortedSet::fromArray(new IntegerComparator(), $values);
        $set2 = new SortedSet(new IntegerComparator(), ...$values);

        static::assertTrue(Equal::areEquals($set1, $set2));
    }

    public function testEquals(): void
    {
        /** @var int[] $values1 */
        $values1 = [1, 2, 3, 3, 1];

        $set1 = new SortedSet(new IntegerComparator(), ...$values1);
        $set2 = new SortedSet(new IntegerComparator(), ...$values1);

        /** @var int[] $values2 */
        $values2 = [4, 4, 5, 2, 3];

        $set3 = new SortedSet(new IntegerComparator(), ...$values2);

        static::assertTrue($set1->equals($set1));
        static::assertTrue($set1->equals($set2));
        static::assertFalse($set1->equals($set3));
        static::assertFalse($set1->equals(new stdClass()));
    }

    public function testHash(): void
    {
        /** @var int[] $values1 */
        $values1 = [1, 2, 3, 3, 1];

        $set1 = new SortedSet(new IntegerComparator(), ...$values1);
        $set2 = new SortedSet(new IntegerComparator(), ...$values1);

        /** @var int[] $values2 */
        $values2 = [4, 4, 5, 2, 3];

        $set3 = new SortedSet(new IntegerComparator(), ...$values2);

        static::assertSame($set1->hash(), $set1->hash());
        static::assertSame($set1->hash(), $set2->hash());
        static::assertNotSame($set1->hash(), $set3->hash());
    }
}
