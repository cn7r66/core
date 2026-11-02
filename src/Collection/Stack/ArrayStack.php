<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Stack;

use ArrayIterator;
use Closure;
use Iterator;
use Override;
use Vivarium\Collection\Exception\CollectionIsEmpty;
use Vivarium\Collection\Sequence\ArraySequence;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;

use function array_filter;
use function array_map;
use function array_reduce;
use function array_reverse;
use function count;

/**
 * @template T
 * @template-implements Stack<T>
 */
final class ArrayStack implements Stack
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
     * @return ArrayStack<K>
     *
     * @template K
     */
    public static function fromArray(array $elements): ArrayStack
    {
        return new ArrayStack(...$elements);
    }

    /**
     * @param T $element
     *
     * @return ArrayStack<T>
     */
    public function push($element): ArrayStack
    {
        $stack           = clone $this;
        $stack->elements = $stack->elements->add($element);

        return $stack;
    }

    /** @return ArrayStack<T> */
    public function pop(): ArrayStack
    {
        if ($this->isEmpty()) {
            throw new CollectionIsEmpty('pop', $this);
        }

        $stack           = clone $this;
        $stack->elements = $stack->elements->removeAtIndex($this->count() - 1);

        return $stack;
    }

    /**
     * @param T $element
     *
     * @return ArrayStack<T>
     */
    public function add($element): ArrayStack
    {
        return $this->push($element);
    }

    /**
     * @param T $element
     *
     * @return ArrayStack<T>
     */
    public function remove($element): ArrayStack
    {
        $elements = $this->elements->remove($element);
        if ($elements === $this->elements) {
            return $this;
        }

        $stack           = clone $this;
        $stack->elements = $elements;

        return $stack;
    }

    /** @param T $element */
    public function contains($element): bool
    {
        return $this->elements->contains($element);
    }

    /** @return T */
    public function peek()
    {
        if ($this->isEmpty()) {
            throw new CollectionIsEmpty('peek', $this);
        }

        return $this->elements->getAtIndex($this->count() - 1);
    }

    /** @return ArrayStack<T> */
    public function clear(): ArrayStack
    {
        return new ArrayStack();
    }

    /**
     * @param Closure(T): bool $predicate
     *
     * @return ArrayStack<T>
     */
    #[Override]
    public function filter(Closure $predicate): ArrayStack
    {
        return new ArrayStack(...array_reverse(array_filter($this->toArray(), $predicate)));
    }

    /**
     * @param Closure(T): U $mapper
     *
     * @return ArrayStack<U>
     *
     * @template U
     */
    #[Override]
    public function map(Closure $mapper): ArrayStack
    {
        return new ArrayStack(...array_reverse(array_map($mapper, $this->toArray())));
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
    public function toArray(): array
    {
        return array_reverse($this->elements->toArray());
    }

    /** @return Iterator<int, T> */
    public function getIterator(): Iterator
    {
        return new ArrayIterator(
            array_reverse(
                $this->elements->toArray(),
            ),
        );
    }

    public function count(): int
    {
        return count($this->elements);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function equals(object $object): bool
    {
        if (! $object instanceof ArrayStack) {
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
