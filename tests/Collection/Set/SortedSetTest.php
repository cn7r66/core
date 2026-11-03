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
use Vivarium\Collection\Set\SortedSet;
use Vivarium\Comparator\ByByte;
use Vivarium\Comparator\ByNumber;
use Vivarium\Comparator\Comparator;
use Vivarium\Equality\Equal;
use Vivarium\Equality\Equality;

use function array_reverse;
use function count;

#[CoversClass(SortedSet::class)]
class SortedSetTest extends TestCase
{
    public function testAdd(): void
    {
        /** @var int[] $values */
        $values = [5, 2, 4, 3, 1];

        $set = new SortedSet(new ByNumber(), ...$values);
        $set = $set
            ->add(3)
            ->add(6);

        $expected = [1, 2, 3, 4, 5, 6];

        static::assertCount(6, $set);
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
        $collection = SortedSet::fromArray(new ByNumber(), $elements);
        $removed    = $collection->remove($element);

        static::assertSame($expected, $removed->toArray());
        static::assertFalse($removed->contains($element));
        static::assertNotSame($collection, $removed);
    }

    public function testRemoveMissing(): void
    {
        $collection = SortedSet::fromArray(new ByNumber(), [1, 2, 3]);

        static::assertSame($collection, $collection->remove(4));
    }

    public function testClear(): void
    {
        /** @var int[] $values */
        $values = [3, 2, 1];

        $set = new SortedSet(new ByNumber(), ...$values);
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

        $set   = SortedSet::fromArray(new ByNumber(), $values);
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

        $set = new SortedSet(new ByByte(), ...$values);

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
                [$stub3, false],
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

        $set1 = new SortedSet(new ByNumber(), ...$values1);
        $set2 = new SortedSet(new ByNumber(), ...$values2);

        $set = $set1->union($set2);

        $expected = [1, 2, 3, 5, 6, 7, 27, 42];

        static::assertCount(8, $set);
        static::assertEquals($expected, $set->toArray());
    }

    public function testIntersection(): void
    {
        /** @var int[] $values1 */
        $values1 = [5, 4, 2, 3, 6];

        $set1 = new SortedSet(new ByNumber(), ...$values1);

        /** @var int[] $values2 */
        $values2 = [8, 7, 4, 5, 6, 1];

        $set2 = new SortedSet(new ByNumber(), ...$values2);

        $set = $set1->intersection($set2);

        $expected = [4, 5, 6];

        static::assertCount(3, $set);
        static::assertEquals($expected, $set->toArray());
    }

    public function testDifference(): void
    {
        /** @var int[] $values1 */
        $values1 = [4, 5, 1, 2, 3];

        $set1 = new SortedSet(new ByNumber(), ...$values1);

        /** @var int[] $values2 */
        $values2 = [8, 7, 4, 6, 5, 2];

        $set2 = new SortedSet(new ByNumber(), ...$values2);

        $set = $set1->difference($set2);

        $expected = [1, 3];

        static::assertCount(2, $set);
        static::assertEquals($expected, $set->toArray());
    }

    public function testIsSubsetOf(): void
    {
        /** @var int[] $values1 */
        $values1 = [4, 5, 1, 2, 6, 3];

        $set = new SortedSet(new ByNumber(), ...$values1);

        /** @var int[] $values2 */
        $values2 = [3, 2, 1];

        $subset = new SortedSet(new ByNumber(), ...$values2);

        static::assertTrue($subset->isSubsetOf($set));
        static::assertFalse($set->isSubsetOf($subset));
    }

    public function testUnionImmutability(): void
    {
        /** @var int[] $values */
        $values = [1, 2, 3];

        $set1 = new SortedSet(new ByNumber(), ...$values);
        $set2 = new SortedSet(new ByNumber());

        $set = $set1->union($set2);

        static::assertNotSame($set, $set1);
    }

    public function testFromArray(): void
    {
        /** @var int[] $values */
        $values = [3, 3, 2, 2, 1, 1];

        $set1 = SortedSet::fromArray(new ByNumber(), $values);
        $set2 = new SortedSet(new ByNumber(), ...$values);

        static::assertTrue(Equal::areEquals($set1, $set2));
    }

    public function testEquals(): void
    {
        /** @var int[] $values1 */
        $values1 = [1, 2, 3, 3, 1];

        $set1 = new SortedSet(new ByNumber(), ...$values1);
        $set2 = new SortedSet(new ByNumber(), ...$values1);

        /** @var int[] $values2 */
        $values2 = [4, 4, 5, 2, 3];

        $set3 = new SortedSet(new ByNumber(), ...$values2);

        static::assertTrue($set1->equals($set1));
        static::assertTrue($set1->equals($set2));
        static::assertFalse($set1->equals($set3));
        static::assertFalse($set1->equals(new stdClass()));
    }

    public function testHash(): void
    {
        /** @var int[] $values1 */
        $values1 = [1, 2, 3, 3, 1];

        $set1 = new SortedSet(new ByNumber(), ...$values1);
        $set2 = new SortedSet(new ByNumber(), ...$values1);

        /** @var int[] $values2 */
        $values2 = [4, 4, 5, 2, 3];

        $set3 = new SortedSet(new ByNumber(), ...$values2);

        static::assertSame($set1->hash(), $set1->hash());
        static::assertSame($set1->hash(), $set2->hash());
        static::assertNotSame($set1->hash(), $set3->hash());
    }

    /**
     * @param list<int> $elements
     * @param list<int> $expected
     */
    #[DataProvider('provideFilter')]
    public function testFilter(array $elements, array $expected): void
    {
        $collection = SortedSet::fromArray(new ByNumber(), $elements);
        $filtered   = $collection->filter(static fn (int|float $element): bool => $element > 1);

        static::assertSame($expected, $filtered->toArray());
        static::assertCount(count($elements), $collection);
        static::assertNotSame($collection, $filtered);
        static::assertSame([0, ...$expected], $filtered->add(0)->toArray());
    }

    /**
     * @param list<int>  $elements
     * @param list<bool> $expected
     */
    #[DataProvider('provideMap')]
    public function testMap(array $elements, array $expected): void
    {
        $mapped = SortedSet::fromArray(new ByNumber(), $elements)->map(
            static fn (int|float $element): bool => $element > 1,
        );

        static::assertInstanceOf(HashSet::class, $mapped);
        static::assertTrue(HashSet::fromArray($expected)->equals($mapped));
    }

    /** @param list<int> $elements */
    #[DataProvider('provideReduce')]
    public function testReduce(array $elements, string $expected): void
    {
        $collection = SortedSet::fromArray(new ByNumber(), array_reverse($elements));

        static::assertSame($expected, $collection->reduce(
            static fn (string $carry, int|float $element): string => $carry . $element,
            '>',
        ));
    }

    /** @return array<string, array{list<int>, int, list<int>}> */
    public static function provideRemove(): array
    {
        return [
            'inside' => [[5, 2, 3, 1, 7], 3, [1, 2, 5, 7]],
            'last' => [[5, 2, 3, 1, 7], 7, [1, 2, 3, 5]],
        ];
    }

    /** @return array<string, array{list<int>, list<int>}> */
    public static function provideFilter(): array
    {
        return [
            'some kept' => [[3, 1, 4, 2], [2, 3, 4]],
            'none kept' => [[1, 0], []],
            'all kept' => [[3, 2], [2, 3]],
            'empty' => [[], []],
        ];
    }

    /** @return array<string, array{list<int>, list<bool>}> */
    public static function provideMap(): array
    {
        return [
            'elements' => [[3, 1, 2], [false, true]],
            'empty' => [[], []],
        ];
    }

    /** @return array<string, array{list<int>, string}> */
    public static function provideReduce(): array
    {
        return [
            'empty' => [[], '>'],
            'in iteration order' => [[1, 2, 3], '>123'],
        ];
    }
}
