<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Queue;

use ArrayIterator;
use Iterator;
use Override;
use Vivarium\Collection\Exception\CollectionIsEmpty;
use Vivarium\Collection\Util\BinarySearch;
use Vivarium\Collection\Util\Vector;
use Vivarium\Comparator\Comparator;
use Vivarium\Equality\Equal;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;

use function array_any;
use function array_filter;
use function array_slice;
use function array_values;
use function count;
use function usort;

/**
 * Ordered by the comparator, first in first out among elements it holds equivalent.
 *
 * @template T
 * @template-implements Queue<T>
 */
final class SortedQueue implements Queue
{
    /** @var array<int, T> */
    private array $elements;

    /**
     * @param Comparator<T> $comparator
     * @param T             ...$elements
     */
    public function __construct(private Comparator $comparator, ...$elements)
    {
        $elements = array_values($elements);
        usort($elements, $comparator->compare(...));

        $this->elements = $elements;
    }

    /**
     * @param Comparator<K> $comparator
     * @param array<K>      $elements
     *
     * @return SortedQueue<K>
     *
     * @template K
     */
    public static function fromArray(Comparator $comparator, array $elements): SortedQueue
    {
        return new SortedQueue($comparator, ...array_values($elements));
    }

    /**
     * The element goes after every element the comparator finds equal: first in, first out among
     * ties.
     *
     * @param T $element
     *
     * @return SortedQueue<T>
     */
    #[Override]
    public function enqueue($element): SortedQueue
    {
        $miss = (new BinarySearch(
            /**
             * Equal elements compare as lower, so the search always misses, after the last tie.
             *
             * @param T $current
             * @param T $target
             */
            fn ($current, $target): int => $this->comparator->compare($current, $target) <= 0 ? -1 : 1,
        ))->search($this->elements, $element);

        $queue           = clone $this;
        $queue->elements = Vector::insertAtIndex($this->elements, $element, -($miss + 1));

        return $queue;
    }

    /** @return SortedQueue<T> */
    #[Override]
    public function dequeue(): SortedQueue
    {
        if ($this->isEmpty()) {
            throw new CollectionIsEmpty('dequeue', $this);
        }

        $queue           = clone $this;
        $queue->elements = array_slice($this->elements, 1);

        return $queue;
    }

    /** @return T */
    #[Override]
    public function peek()
    {
        if ($this->isEmpty()) {
            throw new CollectionIsEmpty('peek', $this);
        }

        return $this->elements[0];
    }

    /**
     * @param T $element
     *
     * @return SortedQueue<T>
     */
    #[Override]
    public function add($element): SortedQueue
    {
        return $this->enqueue($element);
    }

    /**
     * @param T $element
     *
     * @return SortedQueue<T>
     */
    #[Override]
    public function remove($element): SortedQueue
    {
        $remaining = array_values(array_filter(
            $this->elements,
            /** @param T $current */
            static fn ($current): bool => ! Equal::areEquals($current, $element),
        ));

        if (count($remaining) === count($this->elements)) {
            return $this;
        }

        $queue           = clone $this;
        $queue->elements = $remaining;

        return $queue;
    }

    /** @param T $element */
    #[Override]
    public function contains($element): bool
    {
        return array_any(
            $this->elements,
            /** @param T $current */
            static fn ($current): bool => Equal::areEquals($current, $element),
        );
    }

    /** @return SortedQueue<T> */
    #[Override]
    public function clear(): SortedQueue
    {
        return new SortedQueue($this->comparator);
    }

    /** @return array<int, T> */
    #[Override]
    public function toArray(): array
    {
        return $this->elements;
    }

    /** @return Iterator<int, T> */
    #[Override]
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->elements);
    }

    #[Override]
    public function count(): int
    {
        return count($this->elements);
    }

    #[Override]
    public function isEmpty(): bool
    {
        return $this->elements === [];
    }

    #[Override]
    public function equals(object $object): bool
    {
        if (! $object instanceof SortedQueue) {
            return false;
        }

        if ($object === $this) {
            return true;
        }

        return (new EqualsBuilder())
            ->append($this->elements, $object->elements)
            ->isEquals();
    }

    #[Override]
    public function hash(): string
    {
        return (new HashBuilder())
            ->append($this->elements)
            ->getHashCode();
    }
}
