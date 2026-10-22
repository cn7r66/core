<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Collection\Sequence;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Collection\Sequence\ArraySequence;
use Vivarium\Comparator\Comparator;
use Vivarium\Comparator\IntegerComparator;
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

    public function testRemove(): void
    {
        /** @var ArraySequence<int> $list */
        $list = new ArraySequence(1, 5, 0, -1);
        $list = $list->add(42);
        $list = $list->add(23);

        $list = $list->remove(1);
        $list = $list->remove(0);

        $expected = new ArraySequence(5, -1, 42, 23);

        static::assertCount(4, $list);
        static::assertEquals($expected->toArray(), $list->toArray());
        static::assertNotSame($list, $list->remove(0));
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
        $stub1 = $this->createStub(Equality::class);
        $stub2 = $this->createStub(Equality::class);
        $stub3 = $this->createStub(Equality::class);

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
        $sorted = $list->sort(new IntegerComparator());

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
            [$stub3, false]
        ]);

        /** @var Comparator<Equality>&Stub $comparator */
        $comparator = static::createStub(Comparator::class);

        $comparator->method('__invoke')->willReturnMap([
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
        /** @var array<int, int> $values */
        $values = [1, 2, 3];

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
}
