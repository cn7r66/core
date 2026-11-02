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
use Vivarium\Collection\Queue\PriorityQueue;
use Vivarium\Comparator\Priority;

use function count;
use function iterator_to_array;
use function strtoupper;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

#[CoversClass(PriorityQueue::class)]
final class PriorityQueueTest extends TestCase
{
    public function testConstructor(): void
    {
        $queue = new PriorityQueue('a', 'b', 'c');

        static::assertSame(['a', 'b', 'c'], $queue->toArray());
        static::assertCount(3, $queue);
    }

    public function testFromArray(): void
    {
        static::assertTrue(PriorityQueue::fromArray(['a', 'b'])->equals(new PriorityQueue('a', 'b')));
    }

    /**
     * @param list<array{string, int}> $enqueued
     * @param list<string>             $expected
     */
    #[DataProvider('provideEnqueue')]
    public function testEnqueue(array $enqueued, array $expected): void
    {
        $queue = new PriorityQueue();
        foreach ($enqueued as [$element, $priority]) {
            $queue = $queue->enqueue($element, $priority);
        }

        static::assertSame($expected, $queue->toArray());
    }

    /** @return array<string, array{list<array{string, int}>, list<string>}> */
    public static function provideEnqueue(): array
    {
        return [
            'lowest priority first' => [
                [['high', Priority::HIGH], ['low', Priority::LOW], ['normal', Priority::NORMAL]],
                ['low', 'normal', 'high'],
            ],
            'ties first in first out' => [
                [['a', Priority::NORMAL], ['b', Priority::NORMAL], ['c', Priority::NORMAL]],
                ['a', 'b', 'c'],
            ],
            'ties among other priorities' => [
                [['a', 10], ['x', 0], ['b', 10], ['y', 0], ['c', 20], ['z', 0]],
                ['x', 'y', 'z', 'a', 'b', 'c'],
            ],
            'beyond the constants' => [
                [['last', PHP_INT_MAX], ['first', PHP_INT_MIN]],
                ['first', 'last'],
            ],
        ];
    }

    public function testEnqueueDefaultsToNormal(): void
    {
        $queue = (new PriorityQueue())
            ->enqueue('high', Priority::HIGH)
            ->enqueue('default')
            ->enqueue('low', Priority::LOW);

        static::assertSame(['low', 'default', 'high'], $queue->toArray());
    }

    public function testAdd(): void
    {
        $queue = (new PriorityQueue())
            ->enqueue('high', Priority::HIGH)
            ->add('added');

        static::assertSame(['added', 'high'], $queue->toArray());
    }

    public function testDequeue(): void
    {
        $queue = $this->queueOf([['b', 2], ['a', 1]]);

        static::assertSame('a', $queue->peek());
        static::assertSame('b', $queue->dequeue()->peek());
        static::assertTrue($queue->dequeue()->dequeue()->isEmpty());
    }

    #[DataProvider('provideEmptyQueueCalls')]
    public function testDequeueOnEmpty(string $operation): void
    {
        $queue = new PriorityQueue();

        static::expectException(CollectionIsEmpty::class);
        static::expectExceptionMessage(
            'Expected an element to ' . $operation . '. Got object ' . PriorityQueue::class . ' with no elements.',
        );

        $operation === 'peek' ? $queue->peek() : $queue->dequeue();
    }

    /** @return array<string, array{string}> */
    public static function provideEmptyQueueCalls(): array
    {
        return ['dequeue' => ['dequeue'], 'peek' => ['peek']];
    }

    /**
     * @param list<array{string, int}> $left
     * @param list<array{string, int}> $right
     * @param list<string>             $expected
     */
    #[DataProvider('provideMerge')]
    public function testMerge(array $left, array $right, array $expected): void
    {
        static::assertSame($expected, $this->queueOf($left)->merge($this->queueOf($right))->toArray());
    }

    /** @return array<string, array{list<array{string, int}>, list<array{string, int}>, list<string>}> */
    public static function provideMerge(): array
    {
        return [
            'interleaved priorities' => [
                [['l1', 1], ['l3', 3]],
                [['r0', 0], ['r2', 2], ['r4', 4]],
                ['r0', 'l1', 'r2', 'l3', 'r4'],
            ],
            'ties: left first' => [
                [['l1', 5], ['l2', 5]],
                [['r1', 5], ['r2', 5]],
                ['l1', 'l2', 'r1', 'r2'],
            ],
            'empty right' => [[['l', 1]], [], ['l']],
            'empty left' => [[], [['r', 1]], ['r']],
        ];
    }

    public function testRemove(): void
    {
        $queue = (new PriorityQueue())
            ->enqueue('a', 1)
            ->enqueue('b', 2)
            ->enqueue('a', 3);

        $removed = $queue->remove('a');

        static::assertSame(['b'], $removed->toArray());
        static::assertFalse($removed->contains('a'));
    }

    public function testRemoveMissing(): void
    {
        $queue = new PriorityQueue('a', 'b');

        static::assertSame($queue, $queue->remove('z'));
    }

    public function testContains(): void
    {
        $queue = (new PriorityQueue())->enqueue('a', Priority::HIGH);

        static::assertTrue($queue->contains('a'));
        static::assertFalse($queue->contains('b'));
    }

    public function testClear(): void
    {
        static::assertTrue((new PriorityQueue('a', 'b'))->clear()->isEmpty());
    }

    public function testGetIterator(): void
    {
        $queue = (new PriorityQueue())
            ->enqueue('b', 2)
            ->enqueue('a', 1);

        static::assertSame(['a', 'b'], iterator_to_array($queue));
    }

    public function testEquals(): void
    {
        $queue = (new PriorityQueue())->enqueue('a', 1)->enqueue('b', 2);

        static::assertTrue($queue->equals($queue));
        static::assertTrue($queue->equals((new PriorityQueue())->enqueue('b', 2)->enqueue('a', 1)));
        static::assertFalse($queue->equals((new PriorityQueue())->enqueue('a', 1)->enqueue('b', 3)));
        static::assertFalse($queue->equals(new stdClass()));
    }

    public function testHash(): void
    {
        $queue = (new PriorityQueue())->enqueue('a', 1)->enqueue('b', 2);

        static::assertSame($queue->hash(), (new PriorityQueue())->enqueue('b', 2)->enqueue('a', 1)->hash());
        static::assertNotSame($queue->hash(), (new PriorityQueue())->enqueue('a', 1)->enqueue('b', 3)->hash());
    }

    /**
     * @param list<array{string, int}> $entries
     * @param list<string>             $expected
     */
    #[DataProvider('provideFilter')]
    public function testFilter(array $entries, array $expected): void
    {
        $queue    = $this->queueOf($entries);
        $filtered = $queue->filter(static fn (string $element): bool => $element !== 'b');

        static::assertSame($expected, $filtered->toArray());
        static::assertCount(count($entries), $queue);
        static::assertNotSame($queue, $filtered);
    }

    /** @return array<string, array{list<array{string, int}>, list<string>}> */
    public static function provideFilter(): array
    {
        return [
            'some kept' => [[['a', 5], ['b', 1], ['c', 3]], ['c', 'a']],
            'none kept' => [[['b', 1], ['b', 2]], []],
            'all kept' => [[['a', 5], ['c', 3]], ['c', 'a']],
            'empty' => [[], []],
        ];
    }

    /**
     * @param list<array{string, int}> $entries
     * @param list<string>             $expected
     */
    #[DataProvider('provideMap')]
    public function testMap(array $entries, array $expected): void
    {
        static::assertSame($expected, $this->queueOf($entries)->map(strtoupper(...))->toArray());
    }

    /** @return array<string, array{list<array{string, int}>, list<string>}> */
    public static function provideMap(): array
    {
        return [
            'elements' => [[['a', 5], ['b', 1], ['c', 3]], ['B', 'C', 'A']],
            'empty' => [[], []],
        ];
    }

    public function testFilterKeepsPriorities(): void
    {
        $queue    = $this->queueOf([['a', 5], ['b', 1], ['c', 3]]);
        $filtered = $queue->filter(static fn (string $element): bool => $element !== 'b');

        static::assertSame(['c', 'd', 'a'], $filtered->enqueue('d', 4)->toArray());
    }

    public function testMapKeepsPriorities(): void
    {
        $mapped = $this->queueOf([['a', 5], ['b', 1], ['c', 3]])->map(strtoupper(...));

        static::assertSame(['B', 'C', 'X', 'A'], $mapped->enqueue('X', 4)->toArray());
    }

    public function testReduce(): void
    {
        $concat = static fn (string $carry, string $element): string => $carry . $element;

        static::assertSame('>bca', $this->queueOf([['a', 5], ['b', 1], ['c', 3]])->reduce($concat, '>'));
        static::assertSame('>', (new PriorityQueue())->reduce($concat, '>'));
    }

    /**
     * @param list<array{string, int}> $entries
     *
     * @return PriorityQueue<string>
     */
    private function queueOf(array $entries): PriorityQueue
    {
        $queue = new PriorityQueue();
        foreach ($entries as [$element, $priority]) {
            $queue = $queue->enqueue($element, $priority);
        }

        return $queue;
    }
}
