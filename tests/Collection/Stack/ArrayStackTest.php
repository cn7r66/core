<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Collection\Stack;

use ArrayObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Collection\Exception\CollectionIsEmpty;
use Vivarium\Collection\Stack\ArrayStack;
use Vivarium\Equality\Equal;

use function count;

#[CoversClass(ArrayStack::class)]
class ArrayStackTest extends TestCase
{
    public function testPush(): void
    {
        /** @var ArrayStack<int> $stack */
        $stack = new ArrayStack(1, 2, 3);
        $stack = $stack->push(4);

        static::assertCount(4, $stack);
        static::assertEquals(4, $stack->peek());
        static::assertEquals([4, 3, 2, 1], $stack->toArray());
        static::assertNotSame($stack, $stack->push(42));
    }

    public function testAdd(): void
    {
        /** @var ArrayStack<int> $stack */
        $stack = new ArrayStack(1, 2, 3);
        $stack = $stack->add(4);

        static::assertCount(4, $stack);
        static::assertEquals(4, $stack->peek());
        static::assertEquals([4, 3, 2, 1], $stack->toArray());
        static::assertNotSame($stack, $stack->push(42));
    }

    public function testPop(): void
    {
        /** @var array<int, string> $elements */
        $elements = ['a', 'b', 'c', 'd'];
        $stack    = ArrayStack::fromArray($elements);

        $index = 3;
        while (! $stack->isEmpty()) {
            $element = $stack->peek();
            $stack   = $stack->pop();

            static::assertEquals($elements[$index], $element);
            --$index;
        }

        static::assertTrue($stack->isEmpty());

        $stack = $stack->push('e');

        static::assertNotSame($stack, $stack->pop());
    }

    /**
     * @param list<int> $elements
     * @param list<int> $expected
     */
    #[DataProvider('provideRemove')]
    public function testRemove(array $elements, int $element, array $expected): void
    {
        $collection = ArrayStack::fromArray($elements);
        $removed    = $collection->remove($element);

        static::assertSame($expected, $removed->toArray());
        static::assertFalse($removed->contains($element));
        static::assertNotSame($collection, $removed);
    }

    /** @return array<string, array{list<int>, int, list<int>}> */
    public static function provideRemove(): array
    {
        return [
            'top' => [[1, 2, 3], 3, [2, 1]],
            'inside, neighbours kept' => [[1, 2, 3, 4, 5, 6], 4, [6, 5, 3, 2, 1]],
            'every occurrence' => [[1, 2, 1, 3, 1], 1, [3, 2]],
        ];
    }

    public function testRemoveMissing(): void
    {
        $collection = ArrayStack::fromArray([1, 2, 3]);

        static::assertSame($collection, $collection->remove(4));
    }

    public function testPopOnEmpty(): void
    {
        static::expectException(CollectionIsEmpty::class);
        static::expectExceptionMessage(
            'Expected an element to pop. Got object ' . ArrayStack::class . ' with no elements.',
        );

        $stack = new ArrayStack();

        $stack->pop();
    }

    public function testPeekOnEmpty(): void
    {
        static::expectException(CollectionIsEmpty::class);
        static::expectExceptionMessage(
            'Expected an element to peek. Got object ' . ArrayStack::class . ' with no elements.',
        );

        $stack = new ArrayStack();
        $stack->peek();
    }

    public function testContains(): void
    {
        /** @var ArrayStack<int> $stack */
        $stack = new ArrayStack(1, 2, 3, 4, 5);

        static::assertTrue($stack->contains(3));
        static::assertFalse($stack->contains(42));
    }

    public function testClear(): void
    {
        $stack = new ArrayStack(1, 2, 3);
        $stack = $stack->clear();

        static::assertCount(0, $stack);
    }

    public function testGetIterator(): void
    {
        $stack = new ArrayStack(1, 2, 3);
        /** @var array<int> $order */
        $order = [3, 2, 1];

        $index    = 0;
        $iterator = $stack->getIterator();
        while ($iterator->valid()) {
            static::assertEquals($order[$index], $iterator->current());
            $iterator->next();
            ++$index;
        }
    }

    public function testFromArray(): void
    {
        $stack1 = ArrayStack::fromArray([1, 2, 3, 4, 5]);
        $stack2 = new ArrayStack(1, 2, 3, 4, 5);

        static::assertTrue(Equal::areEquals($stack1, $stack2));
    }

    public function testEquals(): void
    {
        $stack1 = new ArrayStack(1, 2, 3);
        $stack2 = new ArrayStack(1, 2, 3);
        $stack3 = new ArrayStack(4, 5, 6);

        static::assertTrue($stack1->equals($stack1));
        static::assertTrue($stack1->equals($stack2));
        static::assertFalse($stack1->equals($stack3));
        static::assertFalse($stack1->equals(new stdClass()));
    }

    public function testHash(): void
    {
        $stack1 = new ArrayStack(1, 2, 3);
        $stack2 = new ArrayStack(1, 2, 3);
        $stack3 = new ArrayStack(4, 5, 6);

        static::assertSame($stack1->hash(), $stack1->hash());
        static::assertSame($stack1->hash(), $stack2->hash());
        static::assertNotSame($stack1->hash(), $stack3->hash());
    }

    /**
     * @param list<int> $elements
     * @param list<int> $expected
     */
    #[DataProvider('provideFilter')]
    public function testFilter(array $elements, array $expected): void
    {
        $collection = ArrayStack::fromArray($elements);
        $filtered   = $collection->filter(static fn (int $element): bool => $element % 2 === 1);

        static::assertSame($expected, $filtered->toArray());
        static::assertCount(count($elements), $collection);
        static::assertNotSame($collection, $filtered);
    }

    /** @return array<string, array{list<int>, list<int>}> */
    public static function provideFilter(): array
    {
        return [
            'some kept' => [[1, 2, 3, 4], [3, 1]],
            'none kept' => [[2, 4], []],
            'all kept' => [[1, 3], [3, 1]],
            'empty' => [[], []],
        ];
    }

    /**
     * @param list<int>    $elements
     * @param list<string> $expected
     */
    #[DataProvider('provideMap')]
    public function testMap(array $elements, array $expected): void
    {
        $mapped = ArrayStack::fromArray($elements)->map(static fn (int $element): string => (string) $element);

        static::assertSame($expected, $mapped->toArray());
    }

    /** @return array<string, array{list<int>, list<string>}> */
    public static function provideMap(): array
    {
        return [
            'elements' => [[1, 2, 3], ['3', '2', '1']],
            'empty' => [[], []],
        ];
    }

    public function testVisitsInIterationOrder(): void
    {
        $stack   = ArrayStack::fromArray([1, 2, 3]);
        $visited = new ArrayObject();
        $record  = static function (int $element) use ($visited): bool {
            $visited->append($element);

            return true;
        };

        $stack->filter($record);
        $stack->map($record);

        static::assertSame([3, 2, 1, 3, 2, 1], $visited->getArrayCopy());
    }

    /** @param list<int> $elements */
    #[DataProvider('provideReduce')]
    public function testReduce(array $elements, string $expected): void
    {
        $collection = ArrayStack::fromArray($elements);

        static::assertSame($expected, $collection->reduce(
            static fn (string $carry, int $element): string => $carry . $element,
            '>',
        ));
    }

    /** @return array<string, array{list<int>, string}> */
    public static function provideReduce(): array
    {
        return [
            'empty' => [[], '>'],
            'in iteration order' => [[1, 2, 3], '>321'],
        ];
    }
}
