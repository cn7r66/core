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
use Vivarium\Collection\Queue\SortedQueue;
use Vivarium\Comparator\ByByte;
use Vivarium\Comparator\ByNumber;
use Vivarium\Equality\Equal;

use function array_reverse;
use function count;
use function usort;

#[CoversClass(SortedQueue::class)]
class SortedQueueTest extends TestCase
{
    public function testEnqueue(): void
    {
        $queue = new SortedQueue(new ByNumber());
        $queue = $queue
            ->enqueue(4)
            ->enqueue(3)
            ->enqueue(3)
            ->enqueue(1);

        static::assertCount(4, $queue);
        static::assertEquals(1, $queue->peek());
        static::assertNotSame($queue, $queue->enqueue(7));
    }

    public function testConstructorKeepsTiesInOrder(): void
    {
        $queue = new SortedQueue(new ByNumber(), 2, 1.0, 3, 1);

        static::assertSame([1.0, 1, 2, 3], $queue->toArray());
    }

    public function testEnqueueKeepsTiesInOrder(): void
    {
        $queue = (new SortedQueue(new ByNumber(), 1, 2))
            ->enqueue(1.0)
            ->enqueue(2.0);

        static::assertSame([1, 1.0, 2, 2.0], $queue->toArray());
    }

    #[DataProvider('provideEmptyQueueCalls')]
    public function testDequeueOnEmpty(string $operation): void
    {
        $queue = new SortedQueue(new ByNumber());

        static::expectException(CollectionIsEmpty::class);
        static::expectExceptionMessage(
            'Expected an element to ' . $operation . '. Got object ' . SortedQueue::class . ' with no elements.',
        );

        $operation === 'peek' ? $queue->peek() : $queue->dequeue();
    }

    /** @return array<string, array{string}> */
    public static function provideEmptyQueueCalls(): array
    {
        return ['dequeue' => ['dequeue'], 'peek' => ['peek']];
    }

    public function testAdd(): void
    {
        /** @var int[] $values */
        $values = [1, 2, 3];

        $queue = new SortedQueue(new ByNumber(), ...$values);
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
        $queue    = SortedQueue::fromArray(new ByByte(), $elements);

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

        $queue = new SortedQueue(new ByNumber(), ...$values);

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
        $collection = SortedQueue::fromArray(new ByNumber(), $elements);
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
        $collection = SortedQueue::fromArray(new ByNumber(), [1, 2, 3]);

        static::assertSame($collection, $collection->remove(4));
    }

    public function testClear(): void
    {
        /** @var int[] $values */
        $values = [5, 2, 1];

        $queue = new SortedQueue(new ByNumber(), ...$values);
        $queue = $queue->clear();

        static::assertCount(0, $queue);
    }

    public function testGetIterator(): void
    {
        /** @var int[] $values */
        $values = [3, 2, 1];

        $queue = new SortedQueue(new ByNumber(), ...$values);
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

        $queue = new SortedQueue(new ByNumber(), ...$values);

        $expected = [1, 3, 6, 23, 42];

        static::assertEquals($expected, $queue->toArray());
    }

    public function testFromArray(): void
    {
        /** @var int[] $values */
        $values = [1, 2, 3];

        $queue1 = SortedQueue::fromArray(new ByNumber(), $values);
        $queue2 = new SortedQueue(new ByNumber(), ...$values);

        static::assertTrue(Equal::areEquals($queue1, $queue2));
    }

    public function testEquals(): void
    {
        /** @var int[] $values1 */
        $values1 = [1, 2, 3];

        $queue1 = new SortedQueue(new ByNumber(), ...$values1);
        $queue2 = new SortedQueue(new ByNumber(), ...$values1);

        /** @var int[] $values2 */
        $values2 = [4, 5, 6];

        $queue3 = new SortedQueue(new ByNumber(), ...$values2);

        static::assertTrue($queue1->equals($queue1));
        static::assertTrue($queue1->equals($queue2));
        static::assertFalse($queue1->equals($queue3));
        static::assertFalse($queue1->equals(new stdClass()));
    }

    public function testHash(): void
    {
        /** @var int[] $values1 */
        $values1 = [1, 2, 3];

        $queue1 = new SortedQueue(new ByNumber(), ...$values1);
        $queue2 = new SortedQueue(new ByNumber(), ...$values1);

        /** @var int[] $values2 */
        $values2 = [4, 5, 6];

        $queue3 = new SortedQueue(new ByNumber(), ...$values2);

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
        $collection = SortedQueue::fromArray(new ByNumber(), $elements);
        $filtered   = $collection->filter(static fn (int|float $element): bool => $element > 1);

        static::assertSame($expected, $filtered->toArray());
        static::assertCount(count($elements), $collection);
        static::assertNotSame($collection, $filtered);
        static::assertSame([0, ...$expected], $filtered->enqueue(0)->toArray());
    }

    /** @return array<string, array{list<int>, list<int>}> */
    public static function provideFilter(): array
    {
        return [
            'some kept' => [[3, 1, 4, 2], [2, 3, 4]],
            'none kept' => [[1, 0], []],
            'all kept' => [[3, 2, 3], [2, 3, 3]],
            'empty' => [[], []],
        ];
    }

    /**
     * @param list<int> $elements
     * @param list<int> $expected
     */
    #[DataProvider('provideMap')]
    public function testMap(array $elements, array $expected): void
    {
        $mapped = SortedQueue::fromArray(new ByNumber(), $elements)->map(
            static fn (int|float $element): int|float => -$element,
        );

        static::assertInstanceOf(ArrayQueue::class, $mapped);
        static::assertSame($expected, $mapped->toArray());
        static::assertSame([...$expected, 0], $mapped->enqueue(0)->toArray());
    }

    /** @return array<string, array{list<int>, list<int>}> */
    public static function provideMap(): array
    {
        return [
            'elements' => [[3, 1, 2], [-1, -2, -3]],
            'empty' => [[], []],
        ];
    }

    /** @param list<int> $elements */
    #[DataProvider('provideReduce')]
    public function testReduce(array $elements, string $expected): void
    {
        $collection = SortedQueue::fromArray(new ByNumber(), array_reverse($elements));

        static::assertSame($expected, $collection->reduce(
            static fn (string $carry, int|float $element): string => $carry . $element,
            '>',
        ));
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
