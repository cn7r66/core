<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Queue;

use Iterator;
use Vivarium\Comparator\Comparator;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;

use function usort;

/**
 * @template T
 * @template-implements Queue<T>
 */
final class PriorityQueue implements Queue
{
    /** @var ArrayQueue<T> */
    private ArrayQueue $queue;

    /**
     * @param Comparator<T> $comparator
     * @param T             ...$elements
     */
    public function __construct(private Comparator $comparator, ...$elements)
    {
        usort($elements, $comparator->compare(...));

        $this->queue = new ArrayQueue(...$elements);
    }

    /**
     * @param Comparator<K> $comparator
     * @param array<K>      $elements
     *
     * @return PriorityQueue<K>
     *
     * @template K
     */
    public static function fromArray(Comparator $comparator, array $elements): PriorityQueue
    {
        return new PriorityQueue($comparator, ...$elements);
    }

    /**
     * @param T $element
     *
     * @return PriorityQueue<T>
     */
    public function add($element): PriorityQueue
    {
        return $this->enqueue($element);
    }

    /**
     * @param T $element
     *
     * @return PriorityQueue<T>
     */
    public function remove($element): PriorityQueue
    {
        $queue = $this->queue->remove($element);
        if ($queue === $this->queue) {
            return $this;
        }

        $priority        = clone $this;
        $priority->queue = $queue;

        return $priority;
    }

    /** @return Iterator<int, T> */
    public function getIterator(): Iterator
    {
        return $this->queue->getIterator();
    }

    public function count(): int
    {
        return $this->queue->count();
    }

    /** @return PriorityQueue<T> */
    public function clear(): PriorityQueue
    {
        return new PriorityQueue($this->comparator);
    }

    /**
     * @param T $element
     *
     * @return PriorityQueue<T>
     */
    public function enqueue($element): PriorityQueue
    {
        $priority   = clone $this;
        $elements   = $priority->queue->toArray();
        $elements[] = $element;

        usort($elements, $this->comparator->compare(...));
        $priority->queue = new ArrayQueue(...$elements);

        return $priority;
    }

    /** @return PriorityQueue<T> */
    public function dequeue(): Queue
    {
        $priorityQueue        = clone $this;
        $priorityQueue->queue = $priorityQueue->queue->dequeue();

        return $priorityQueue;
    }

    /** @return T */
    public function peek()
    {
        return $this->queue->peek();
    }

    /** @return array<int, T> */
    public function toArray(): array
    {
        return $this->queue->toArray();
    }

    /** @param T $element */
    public function contains($element): bool
    {
        return $this->queue->contains($element);
    }

    public function isEmpty(): bool
    {
        return $this->queue->isEmpty();
    }

    public function equals(object $object): bool
    {
        if (! $object instanceof PriorityQueue) {
            return false;
        }

        if ($object === $this) {
            return true;
        }

        return (new EqualsBuilder())
            ->append($this->queue, $object->queue)
            ->isEquals();
    }

    public function hash(): string
    {
        return (new HashBuilder())
            ->append($this->queue)
            ->getHashCode();
    }
}
