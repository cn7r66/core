<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Collection\Sequence;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Collection\Sequence\ArraySequence;
use Vivarium\Comparator\ByNumber;
use Vivarium\Comparator\Comparator;
use Vivarium\Equality\Equal;
use Vivarium\Equality\Equality;

#[CoversClass(ArraySequence::class)]
class ArraySequenceTest extends TestCase
{
    public function testAdd(): void
    {
        /** @var ArraySequence<int> $list */
        $list = new ArraySequence(1, 5, 0, -1);
        $list = $list->add(42);
        $list = $list->add(23);

        $expected = new ArraySequence(1, 5, 0, -1, 42, 23);

        static::assertCount(6, $list);
        static::assertEquals($expected->toArray(), $list->toArray());
        static::assertNotSame($list, $list->add(1));
    }

    /**
     * @param list<int> $elements
     * @param list<int> $expected
     */
    #[DataProvider('provideRemove')]
    public function testRemove(array $elements, int $element, array $expected): void
    {
        $collection = ArraySequence::fromArray($elements);
        $removed    = $collection->remove($element);

        static::assertSame($expected, $removed->toArray());
        static::assertFalse($removed->contains($element));
        static::assertNotSame($collection, $removed);
    }

    public function testRemoveMissing(): void
    {
        $collection = ArraySequence::fromArray([1, 2, 3]);

        static::assertSame($collection, $collection->remove(4));
    }

    public function testContains(): void
    {
        /** @var ArraySequence<int> $list */
        $list = new ArraySequence(5, 3, 42, 11, 0, -1);

        static::assertTrue($list->contains(5));
        static::assertTrue($list->contains(-1));
        static::assertTrue($list->contains(42));
        static::assertFalse($list->contains(117));
    }

    public function testClear(): void
    {
        $list = new ArraySequence(1, 2, 3, 4, 5);
        $list = $list->clear();

        static::assertCount(0, $list);
        static::assertTrue($list->isEmpty());
    }

    public function testGetIterator(): void
    {
        /** @var array<int, int> $expected */
        $expected = [1, 2, 3, 4, 5];

        $list = ArraySequence::fromArray($expected);

        $index = 0;
        foreach ($list as $item) {
            static::assertEquals($expected[$index], $item);
            ++$index;
        }
    }

    public function testGetAtIndex(): void
    {
        $list    = new ArraySequence(1, 2, 3, 4, 5);
        $element = $list->getAtIndex(2);

        static::assertEquals(3, $element);
    }

    public function testSetAtIndex(): void
    {
        /** @var ArraySequence<int> $list */
        $list    = new ArraySequence(1, 2, 3, 4, 5);
        $element = $list->setAtIndex(1, 5);

        static::assertEquals(5, $element->getAtIndex(1));
        static::assertNotSame($list, $list->setAtIndex(0, 42));
    }

    public function testRemoveAtIndex(): void
    {
        $list = new ArraySequence(1, 2, 3, 4, 5);
        $list = $list->removeAtIndex(1);

        static::assertEquals([1, 3, 4, 5], $list->toArray());
        static::assertNotSame($list, $list->removeAtIndex(0));
    }

    public function testSearch(): void
    {
        /** @var ArraySequence<int> $list */
        $list = new ArraySequence(5, 2, 42, 7, 11);

        static::assertEquals(0, $list->search(5));
        static::assertEquals(2, $list->search(42));
        static::assertEquals(-6, $list->search(35));

        $empty = $list->clear();
        static::assertEquals(-1, $empty->search(2));
    }

    public function testSearchWithEquality(): void
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

        $list = new ArraySequence($stub1, $stub2, $stub3);

        static::assertEquals(1, $list->search($stub2));
    }

    public function testSort(): void
    {
        /** @var ArraySequence<int> $list */
        $list   = new ArraySequence(5, 2, 7, 8, 1);
        $sorted = $list->sort(new ByNumber());

        $expected = new ArraySequence(1, 2, 5, 7, 8);

        static::assertEquals($expected->toArray(), $sorted->toArray());
        static::assertNotSame($list, $sorted);
    }

    public function testSortWithObject(): void
    {
        $stub1 = static::createStub(Equality::class);
        $stub2 = static::createStub(Equality::class);
        $stub3 = static::createStub(Equality::class);

        $stub2->method('equals')->willReturnMap([
            [$stub1, false],
            [$stub2, true],
            [$stub3, false],
        ]);

        /** @var Comparator<Equality>&Stub $comparator */
        $comparator = static::createStub(Comparator::class);

        $comparator->method('compare')->willReturnMap([
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

        /** @var ArraySequence<Equality> $list */
        $list = new ArraySequence($stub2, $stub1, $stub3);
        $list = $list->sort($comparator);

        $expected = new ArraySequence($stub1, $stub2, $stub3);

        static::assertEquals($expected->toArray(), $list->toArray());
    }

    public function testFromArray(): void
    {
        $values = ['k0' => 1, 'k1' => 2, 'k2' => 3];

        $list1 = ArraySequence::fromArray($values);
        $list2 = new ArraySequence(1, 2, 3);

        static::assertTrue(Equal::areEquals($list1, $list2));
    }

    public function testEquals(): void
    {
        $list1 = new ArraySequence(1, 2, 3);
        $list2 = new ArraySequence(1, 2, 3);
        $list3 = new ArraySequence(4, 5, 6);

        static::assertTrue($list1->equals($list1));
        static::assertTrue($list1->equals($list2));
        static::assertFalse($list1->equals($list3));
        static::assertFalse($list1->equals(new stdClass()));
    }

    public function testHash(): void
    {
        $list1 = new ArraySequence(1, 2, 3);
        $list2 = new ArraySequence(1, 2, 3);
        $list3 = new ArraySequence(4, 5, 6);

        static::assertSame($list1->hash(), $list1->hash());
        static::assertSame($list1->hash(), $list2->hash());
        static::assertNotSame($list1->hash(), $list3->hash());
    }

    /**
     * @param list<int> $elements
     * @param list<int> $expected
     */
    #[DataProvider('provideFilter')]
    public function testFilter(array $elements, array $expected): void
    {
        $collection = ArraySequence::fromArray($elements);
        $filtered   = $collection->filter(static fn (int $element): bool => $element % 2 === 1);

        static::assertSame($expected, $filtered->toArray());
        static::assertSame($elements, $collection->toArray());
        static::assertNotSame($collection, $filtered);
    }

    /**
     * @param list<int>    $elements
     * @param list<string> $expected
     */
    #[DataProvider('provideMap')]
    public function testMap(array $elements, array $expected): void
    {
        $mapped = ArraySequence::fromArray($elements)->map(static fn (int $element): string => (string) $element);

        static::assertSame($expected, $mapped->toArray());
    }

    /** @param list<int> $elements */
    #[DataProvider('provideReduce')]
    public function testReduce(array $elements, string $expected): void
    {
        $collection = ArraySequence::fromArray($elements);

        static::assertSame($expected, $collection->reduce(
            static fn (string $carry, int $element): string => $carry . $element,
            '>',
        ));
    }

    /** @return array<string, array{list<int>, int, list<int>}> */
    public static function provideRemove(): array
    {
        return [
            'single' => [[1, 5, 0, -1], 0, [1, 5, -1]],
            'every occurrence' => [[1, 2, 1, 3, 1], 1, [2, 3]],
            'only element' => [[7], 7, []],
        ];
    }

    /** @return array<string, array{list<int>, list<int>}> */
    public static function provideFilter(): array
    {
        return [
            'some kept' => [[1, 2, 3, 4, 5], [1, 3, 5]],
            'none kept' => [[2, 4], []],
            'all kept' => [[1, 3], [1, 3]],
            'empty' => [[], []],
        ];
    }

    /** @return array<string, array{list<int>, list<string>}> */
    public static function provideMap(): array
    {
        return [
            'elements' => [[1, 2, 3], ['1', '2', '3']],
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
