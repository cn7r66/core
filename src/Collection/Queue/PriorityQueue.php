<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Queue;

use ArrayIterator;
use Closure;
use Iterator;
use Override;
use Vivarium\Collection\Exception\CollectionIsEmpty;
use Vivarium\Collection\Pair\Pair;
use Vivarium\Collection\Util\BinarySearch;
use Vivarium\Collection\Util\Vector;
use Vivarium\Comparator\Priority;
use Vivarium\Equality\Equal;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;

use function array_any;
use function array_filter;
use function array_map;
use function array_reduce;
use function array_slice;
use function array_values;
use function count;

/**
 * Lowest priority first, first in first out among equal priorities. Priorities stay inside the
 * queue: elements go in with one and come out alone.
 *
 * @template T
 * @template-implements Queue<T>
 */
final class PriorityQueue implements Queue
{
    /** @var array<int, Pair<int, T>> */
    private array $entries = [];

    /** @param T ...$elements */
    public function __construct(...$elements)
    {
        foreach ($elements as $element) {
            $this->entries[] = new Pair(Priority::NORMAL, $element);
        }
    }

    /**
     * @param array<K> $elements
     *
     * @return PriorityQueue<K>
     *
     * @template K
     */
    public static function fromArray(array $elements): PriorityQueue
    {
        return new PriorityQueue(...array_values($elements));
    }

    /**
     * The element goes after every entry with an equal priority: first in, first out among ties.
     *
     * @param T $element
     *
     * @return PriorityQueue<T>
     */
    #[Override]
    public function enqueue($element, int $priority = Priority::NORMAL): PriorityQueue
    {
        $miss = (new BinarySearch(
            /** Equal priorities compare as lower, so the search always misses, after the last tie. */
            static fn (Pair $entry, int $target): int => $entry->getKey() <= $target ? -1 : 1,
        ))->search($this->entries, $priority);

        $queue          = clone $this;
        $queue->entries = Vector::insertAtIndex(
            $this->entries,
            new Pair($priority, $element),
            -($miss + 1),
        );

        return $queue;
    }

    /** @return PriorityQueue<T> */
    #[Override]
    public function dequeue(): PriorityQueue
    {
        if ($this->isEmpty()) {
            throw new CollectionIsEmpty('dequeue', $this);
        }

        $queue          = clone $this;
        $queue->entries = array_slice($this->entries, 1);

        return $queue;
    }

    /** @return T */
    #[Override]
    public function peek()
    {
        if ($this->isEmpty()) {
            throw new CollectionIsEmpty('peek', $this);
        }

        return $this->entries[0]->getValue();
    }

    /**
     * Both queues' elements by priority; on equal priority this queue's come first.
     *
     * @param PriorityQueue<T> $other
     *
     * @return PriorityQueue<T>
     */
    public function merge(PriorityQueue $other): PriorityQueue
    {
        $left      = $this->entries;
        $right     = $other->entries;
        $merged    = [];
        $nextLeft  = 0;
        $nextRight = 0;
        while ($nextLeft < count($left) && $nextRight < count($right)) {
            if ($right[$nextRight]->getKey() < $left[$nextLeft]->getKey()) {
                $merged[] = $right[$nextRight];
                $nextRight++;

                continue;
            }

            $merged[] = $left[$nextLeft];
            $nextLeft++;
        }

        $queue          = clone $this;
        $queue->entries = [...$merged, ...array_slice($left, $nextLeft), ...array_slice($right, $nextRight)];

        return $queue;
    }

    /**
     * @param T $element
     *
     * @return PriorityQueue<T>
     */
    #[Override]
    public function add($element): PriorityQueue
    {
        return $this->enqueue($element);
    }

    /**
     * Removes the element at every priority it was queued with.
     *
     * @param T $element
     *
     * @return PriorityQueue<T>
     */
    #[Override]
    public function remove($element): PriorityQueue
    {
        $remaining = array_values(array_filter(
            $this->entries,
            /** @param Pair<int, T> $entry */
            static fn (Pair $entry): bool => ! Equal::areEquals($entry->getValue(), $element),
        ));

        if (count($remaining) === count($this->entries)) {
            return $this;
        }

        $queue          = clone $this;
        $queue->entries = $remaining;

        return $queue;
    }

    /** @param T $element */
    #[Override]
    public function contains($element): bool
    {
        return array_any(
            $this->entries,
            /** @param Pair<int, T> $entry */
            static fn (Pair $entry): bool => Equal::areEquals($entry->getValue(), $element),
        );
    }

    /** @return PriorityQueue<T> */
    #[Override]
    public function clear(): PriorityQueue
    {
        return new PriorityQueue();
    }

    /**
     * The accepted elements keep their priorities.
     *
     * @param Closure(T): bool $predicate
     *
     * @return PriorityQueue<T>
     */
    #[Override]
    public function filter(Closure $predicate): PriorityQueue
    {
        $queue          = clone $this;
        $queue->entries = array_values(array_filter(
            $this->entries,
            /** @param Pair<int, T> $entry */
            static fn (Pair $entry): bool => $predicate($entry->getValue()),
        ));

        return $queue;
    }

    /**
     * Each mapped element keeps the priority of its source.
     *
     * @param Closure(T): U $mapper
     *
     * @return PriorityQueue<U>
     *
     * @template U
     */
    #[Override]
    public function map(Closure $mapper): PriorityQueue
    {
        $values = array_map($mapper, $this->toArray());

        $queue          = new PriorityQueue(...$values);
        $queue->entries = array_map(
            /**
             * @param Pair<int, T> $entry
             * @param U            $value
             */
            static fn (Pair $entry, $value): Pair => new Pair($entry->getKey(), $value),
            $this->entries,
            $values,
        );

        return $queue;
    }

    /**
     * @param Closure(R, T): R $reducer
     * @param R                $initial
     *
     * @return R
     *
     * @template R
     */
    #[Override]
    public function reduce(Closure $reducer, $initial)
    {
        return array_reduce($this->toArray(), $reducer, $initial);
    }

    /** @return array<int, T> */
    #[Override]
    public function toArray(): array
    {
        return array_map(
            /** @param Pair<int, T> $entry */
            static fn (Pair $entry): mixed => $entry->getValue(),
            $this->entries,
        );
    }

    /** @return Iterator<int, T> */
    #[Override]
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->toArray());
    }

    #[Override]
    public function count(): int
    {
        return count($this->entries);
    }

    #[Override]
    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    #[Override]
    public function equals(object $object): bool
    {
        if (! $object instanceof PriorityQueue) {
            return false;
        }

        if ($object === $this) {
            return true;
        }

        return (new EqualsBuilder())
            ->append($this->entries, $object->entries)
            ->isEquals();
    }

    #[Override]
    public function hash(): string
    {
        return (new HashBuilder())
            ->append($this->entries)
            ->getHashCode();
    }
}
