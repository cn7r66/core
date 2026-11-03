<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Collection\Queue;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Collection\Exception\CollectionIsEmpty;
use Vivarium\Collection\Queue\ArrayQueue;
use Vivarium\Collection\Queue\Queue;
use Vivarium\Equality\Equal;

#[CoversClass(ArrayQueue::class)]
class ArrayQueueTest extends TestCase
{
    public function testEnqueue(): void
    {
        /** @var Queue<int> $queue */
        $queue = new ArrayQueue(1, 2, 3);
        $queue = $queue->enqueue(4);

        static::assertCount(4, $queue);
        static::assertEquals(1, $queue->peek());
        static::assertEquals([1, 2, 3, 4], $queue->toArray());
        static::assertNotSame($queue, $queue->enqueue(5));
    }

    public function testAdd(): void
    {
        /** @var Queue<int> $queue */
        $queue = new ArrayQueue(1, 2, 3);
        $queue = $queue->add(4);

        static::assertCount(4, $queue);
        static::assertEquals(1, $queue->peek());
        static::assertEquals([1, 2, 3, 4], $queue->toArray());
        static::assertNotSame($queue, $queue->enqueue(5));
    }

    public function testDequeue(): void
    {
        /** @var array<int, string> $elements */
        $elements = ['a', 'b', 'c', 'd'];
        $queue    = ArrayQueue::fromArray($elements);

        $index = 0;
        while (! $queue->isEmpty()) {
            $element = $queue->peek();
            $queue   = $queue->dequeue();

            static::assertEquals($elements[$index], $element);
            ++$index;
        }

        static::assertTrue($queue->isEmpty());

        $queue = $queue->enqueue('e');

        static::assertNotSame($queue, $queue->dequeue());
    }

    /**
     * @param list<int> $elements
     * @param list<int> $expected
     */
    #[DataProvider('provideRemove')]
    public function testRemove(array $elements, int $element, array $expected): void
    {
        $collection = ArrayQueue::fromArray($elements);
        $removed    = $collection->remove($element);

        static::assertSame($expected, $removed->toArray());
        static::assertFalse($removed->contains($element));
        static::assertNotSame($collection, $removed);
    }

    public function testRemoveMissing(): void
    {
        $collection = ArrayQueue::fromArray([1, 2, 3]);

        static::assertSame($collection, $collection->remove(4));
    }

    public function testDequeueOnEmpty(): void
    {
        static::expectException(CollectionIsEmpty::class);
        static::expectExceptionMessage(
            'Expected an element to dequeue. Got object ' . ArrayQueue::class . ' with no elements.',
        );

        $queue = new ArrayQueue();
        $queue->dequeue();
    }

    public function testPeekOnEmpty(): void
    {
        static::expectException(CollectionIsEmpty::class);
        static::expectExceptionMessage(
            'Expected an element to peek. Got object ' . ArrayQueue::class . ' with no elements.',
        );

        $queue = new ArrayQueue();
        $queue->peek();
    }

    public function testContains(): void
    {
        /** @var Queue<int> $queue */
        $queue = new ArrayQueue(1, 2, 3, 4, 5);

        static::assertTrue($queue->contains(3));
        static::assertFalse($queue->contains(42));
    }

    public function testClear(): void
    {
        $queue = new ArrayQueue(1, 2, 3);
        $queue = $queue->clear();

        static::assertCount(0, $queue);
    }

    public function testGetIterator(): void
    {
        $queue = new ArrayQueue(1, 2, 3);
        /** @var array<int> $order */
        $order = [1, 2, 3];

        $index    = 0;
        $iterator = $queue->getIterator();
        while ($iterator->valid()) {
            static::assertEquals($order[$index], $iterator->current());
            $iterator->next();
            ++$index;
        }
    }

    public function testFromArray(): void
    {
        $values = [1, 2, 3];

        $queue1 = ArrayQueue::fromArray($values);
        $queue2 = new ArrayQueue(1, 2, 3);

        static::assertTrue(Equal::areEquals($queue1, $queue2));
    }

    public function testEquals(): void
    {
        $queue1 = new ArrayQueue(1, 2, 3);
        $queue2 = new ArrayQueue(1, 2, 3);
        $queue3 = new ArrayQueue(4, 5, 6);

        static::assertTrue($queue1->equals($queue1));
        static::assertTrue($queue1->equals($queue2));
        static::assertFalse($queue1->equals($queue3));
        static::assertFalse($queue1->equals(new stdClass()));
    }

    public function testHash(): void
    {
        $queue1 = new ArrayQueue(1, 2, 3);
        $queue2 = new ArrayQueue(1, 2, 3);
        $queue3 = new ArrayQueue(4, 5, 6);

        static::assertSame($queue1->hash(), $queue1->hash());
        static::assertSame($queue1->hash(), $queue2->hash());
        static::assertNotSame($queue1->hash(), $queue3->hash());
    }

    /**
     * @param list<int> $elements
     * @param list<int> $expected
     */
    #[DataProvider('provideFilter')]
    public function testFilter(array $elements, array $expected): void
    {
        $collection = ArrayQueue::fromArray($elements);
        $filtered   = $collection->filter(static fn (int $element): bool => $element % 2 === 0);

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
        $mapped = ArrayQueue::fromArray($elements)->map(static fn (int $element): string => (string) $element);

        static::assertSame($expected, $mapped->toArray());
    }

    /** @param list<int> $elements */
    #[DataProvider('provideReduce')]
    public function testReduce(array $elements, string $expected): void
    {
        $collection = ArrayQueue::fromArray($elements);

        static::assertSame($expected, $collection->reduce(
            static fn (string $carry, int $element): string => $carry . $element,
            '>',
        ));
    }

    /** @return array<string, array{list<int>, int, list<int>}> */
    public static function provideRemove(): array
    {
        return [
            'head' => [[1, 2, 3, 4], 1, [2, 3, 4]],
            'inside, neighbours kept' => [[1, 2, 3, 4, 5, 6], 4, [1, 2, 3, 5, 6]],
            'every occurrence' => [[1, 2, 1, 3, 1], 1, [2, 3]],
        ];
    }

    /** @return array<string, array{list<int>, list<int>}> */
    public static function provideFilter(): array
    {
        return [
            'some kept' => [[1, 2, 3, 4], [2, 4]],
            'none kept' => [[1, 3], []],
            'all kept' => [[2, 4], [2, 4]],
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
