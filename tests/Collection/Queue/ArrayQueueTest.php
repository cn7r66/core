<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Collection\Queue;

use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
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

    public function testRemove(): void
    {
        /** @var array<int, int> $elements */
        $elements = [1, 2, 3, 4, 5, 6];
        $expected = [2, 3, 4, 5, 6];

        $queue = ArrayQueue::fromArray($elements);
        $queue = $queue->remove(1);

        static::assertEquals($expected, $queue->toArray());
        static::assertNotSame($queue, $queue->remove(0));
    }

    public function testRemoveMultiple(): void
    {
        $elements = [1, 2, 3, 4, 5, 6];
        $expected = [5, 6];

        $queue = ArrayQueue::fromArray($elements);
        $queue = $queue->remove(4);
        $queue = $queue->remove(2);

        static::assertEquals($expected, $queue->toArray());
    }

    public function testDequeueOnEmpty(): void
    {
        static::expectException(DomainException::class);
        static::expectExceptionMessage('Cannot dequeue from an empty queue.');

        $queue = new ArrayQueue();
        /** @psalm-suppress UnusedMethodCall */
        $queue->dequeue();
    }

    public function testPeekOnEmpty(): void
    {
        static::expectException(DomainException::class);
        static::expectExceptionMessage('Cannot peek from an empty queue.');

        $queue = new ArrayQueue();
        /** @psalm-suppress UnusedMethodCall */
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
}
