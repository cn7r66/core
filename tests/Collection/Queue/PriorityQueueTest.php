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
use Vivarium\Collection\Queue\PriorityQueue;
use Vivarium\Comparator\ByByte;
use Vivarium\Comparator\ByNumber;
use Vivarium\Equality\Equal;

use function usort;

#[CoversClass(PriorityQueue::class)]
class PriorityQueueTest extends TestCase
{
    public function testEnqueue(): void
    {
        $queue = new PriorityQueue(new ByNumber());
        $queue = $queue
            ->enqueue(4)
            ->enqueue(3)
            ->enqueue(3)
            ->enqueue(1);

        static::assertCount(4, $queue);
        static::assertEquals(1, $queue->peek());
        static::assertNotSame($queue, $queue->enqueue(7));
    }

    public function testAdd(): void
    {
        /** @var int[] $values */
        $values = [1, 2, 3];

        $queue = new PriorityQueue(new ByNumber(), ...$values);
        $queue = $queue->add(4);

        static::assertCount(4, $queue);
        static::assertEquals(1, $queue->peek());
        static::assertEquals([1, 2, 3, 4], $queue->toArray());
        static::assertNotSame($queue, $queue->enqueue(5));
    }

    public function testDequeue(): void
    {
        /** @var string[] $elements */
        $elements = ['z', 'k', 'v', 'a', 'd'];
        $queue    = PriorityQueue::fromArray(new ByByte(), $elements);

        usort($elements, (new ByByte())->compare(...));

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

    public function testContains(): void
    {
        /** @var int[] $values */
        $values = [1, 2, 3, 4, 5];

        $queue = new PriorityQueue(new ByNumber(), ...$values);

        static::assertTrue($queue->contains(3));
        static::assertFalse($queue->contains(42));
    }

    /**
     * @param list<int> $elements
     * @param list<int> $expected
     */
    #[DataProvider('provideRemove')]
    public function testRemove(array $elements, int $element, array $expected): void
    {
        $collection = PriorityQueue::fromArray(new ByNumber(), $elements);
        $removed    = $collection->remove($element);

        static::assertSame($expected, $removed->toArray());
        static::assertFalse($removed->contains($element));
        static::assertNotSame($collection, $removed);
    }

    /** @return array<string, array{list<int>, int, list<int>}> */
    public static function provideRemove(): array
    {
        return [
            'first in order' => [[3, 4, 5, 1, 2, 6], 1, [2, 3, 4, 5, 6]],
            'every occurrence' => [[3, 1, 2, 1], 1, [2, 3]],
        ];
    }

    public function testRemoveMissing(): void
    {
        $collection = PriorityQueue::fromArray(new ByNumber(), [1, 2, 3]);

        static::assertSame($collection, $collection->remove(4));
    }

    public function testClear(): void
    {
        /** @var int[] $values */
        $values = [5, 2, 1];

        $queue = new PriorityQueue(new ByNumber(), ...$values);
        $queue = $queue->clear();

        static::assertCount(0, $queue);
    }

    public function testGetIterator(): void
    {
        /** @var int[] $values */
        $values = [3, 2, 1];

        $queue = new PriorityQueue(new ByNumber(), ...$values);
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

    public function testToArray(): void
    {
        /** @var int[] $values */
        $values = [42, 6, 3, 23, 1];

        $queue = new PriorityQueue(new ByNumber(), ...$values);

        $expected = [1, 3, 6, 23, 42];

        static::assertEquals($expected, $queue->toArray());
    }

    public function testFromArray(): void
    {
        /** @var int[] $values */
        $values = [1, 2, 3];

        $queue1 = PriorityQueue::fromArray(new ByNumber(), $values);
        $queue2 = new PriorityQueue(new ByNumber(), ...$values);

        static::assertTrue(Equal::areEquals($queue1, $queue2));
    }

    public function testEquals(): void
    {
        /** @var int[] $values1 */
        $values1 = [1, 2, 3];

        $queue1 = new PriorityQueue(new ByNumber(), ...$values1);
        $queue2 = new PriorityQueue(new ByNumber(), ...$values1);

        /** @var int[] $values2 */
        $values2 = [4, 5, 6];

        $queue3 = new PriorityQueue(new ByNumber(), ...$values2);

        static::assertTrue($queue1->equals($queue1));
        static::assertTrue($queue1->equals($queue2));
        static::assertFalse($queue1->equals($queue3));
        static::assertFalse($queue1->equals(new stdClass()));
    }

    public function testHash(): void
    {
        /** @var int[] $values1 */
        $values1 = [1, 2, 3];

        $queue1 = new PriorityQueue(new ByNumber(), ...$values1);
        $queue2 = new PriorityQueue(new ByNumber(), ...$values1);

        /** @var int[] $values2 */
        $values2 = [4, 5, 6];

        $queue3 = new PriorityQueue(new ByNumber(), ...$values2);

        static::assertSame($queue1->hash(), $queue1->hash());
        static::assertSame($queue1->hash(), $queue2->hash());
        static::assertNotSame($queue1->hash(), $queue3->hash());
    }
}
