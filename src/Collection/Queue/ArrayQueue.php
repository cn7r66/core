<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Queue;

use Closure;
use Iterator;
use Override;
use Vivarium\Collection\Exception\CollectionIsEmpty;
use Vivarium\Collection\Sequence\ArraySequence;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;

/**
 * @template T
 * @template-implements Queue<T>
 */
final class ArrayQueue implements Queue
{
    /** @var ArraySequence<T> */
    private ArraySequence $elements;

    /** @param T ...$elements */
    public function __construct(...$elements)
    {
        $this->elements = ArraySequence::fromArray($elements);
    }

    /**
     * @param array<K> $elements
     *
     * @return ArrayQueue<K>
     *
     * @template K
     */
    public static function fromArray(array $elements): ArrayQueue
    {
        return new ArrayQueue(...$elements);
    }

    /**
     * @param T $element
     *
     * @return ArrayQueue<T>
     */
    public function add($element): ArrayQueue
    {
        return $this->enqueue($element);
    }

    /**
     * @param T $element
     *
     * @return ArrayQueue<T>
     */
    public function remove($element): ArrayQueue
    {
        $elements = $this->elements->remove($element);
        if ($elements === $this->elements) {
            return $this;
        }

        $queue           = clone $this;
        $queue->elements = $elements;

        return $queue;
    }

    /** @return ArrayQueue<T> */
    public function clear(): ArrayQueue
    {
        return new ArrayQueue();
    }

    /**
     * @param Closure(T): bool $predicate
     *
     * @return ArrayQueue<T>
     */
    #[Override]
    public function filter(Closure $predicate): ArrayQueue
    {
        $queue           = clone $this;
        $queue->elements = $this->elements->filter($predicate);

        return $queue;
    }

    /**
     * @param Closure(T): U $mapper
     *
     * @return ArrayQueue<U>
     *
     * @template U
     */
    #[Override]
    public function map(Closure $mapper): ArrayQueue
    {
        return new ArrayQueue(...$this->elements->map($mapper)->toArray());
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
        return $this->elements->reduce($reducer, $initial);
    }

    /** @return array<int, T> */
    public function toArray(): array
    {
        return $this->elements->toArray();
    }

    /** @return Iterator<int, T> */
    public function getIterator(): Iterator
    {
        return $this->elements->getIterator();
    }

    public function count(): int
    {
        return $this->elements->count();
    }

    /**
     * @param T $element
     *
     * @return ArrayQueue<T>
     */
    public function enqueue($element): ArrayQueue
    {
        $queue           = clone $this;
        $queue->elements = $queue->elements->add($element);

        return $queue;
    }

    /** @return ArrayQueue<T> */
    public function dequeue(): ArrayQueue
    {
        if ($this->isEmpty()) {
            throw new CollectionIsEmpty('dequeue', $this);
        }

        $queue           = clone $this;
        $queue->elements = $queue->elements->removeAtIndex(0);

        return $queue;
    }

    /** @return T */
    public function peek()
    {
        if ($this->isEmpty()) {
            throw new CollectionIsEmpty('peek', $this);
        }

        return $this->elements->getAtIndex(0);
    }

    /** @param T $element */
    public function contains($element): bool
    {
        return $this->elements->contains($element);
    }

    public function isEmpty(): bool
    {
        return $this->elements->isEmpty();
    }

    public function equals(object $object): bool
    {
        if (! $object instanceof ArrayQueue) {
            return false;
        }

        if ($object === $this) {
            return true;
        }

        return (new EqualsBuilder())
            ->append($this->elements, $object->elements)
            ->isEquals();
    }

    public function hash(): string
    {
        return (new HashBuilder())
            ->append($this->elements)
            ->getHashCode();
    }
}
