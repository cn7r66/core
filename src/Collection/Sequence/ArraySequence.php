<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Sequence;

use ArrayIterator;
use Closure;
use Iterator;
use Override;
use Vivarium\Collection\Exception\IndexOutOfBounds;
use Vivarium\Collection\Util\LinearSearch;
use Vivarium\Comparator\Comparator;
use Vivarium\Equality\Equal;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;

use function array_filter;
use function array_map;
use function array_reduce;
use function array_values;
use function count;
use function usort;

/**
 * @template T
 * @template-implements Sequence<T>
 */
final class ArraySequence implements Sequence
{
    /** @var array<int, T> */
    private array $elements;

    /**
     * @param T ...$elements
     *
     * @no-named-arguments
     */
    public function __construct(...$elements)
    {
        $this->elements = $elements;
    }

    /**
     * @param array<T0> $elements
     *
     * @return ArraySequence<T0>
     *
     * @template T0
     */
    public static function fromArray(array $elements): ArraySequence
    {
        return new ArraySequence(...array_values($elements));
    }

    /**
     * @param T $element
     *
     * @return ArraySequence<T>
     */
    public function add($element): ArraySequence
    {
        $list             = clone $this;
        $list->elements[] = $element;

        return $list;
    }

    /**
     * @param T $element
     *
     * @return ArraySequence<T>
     */
    public function remove($element): ArraySequence
    {
        $remaining = array_values(array_filter(
            $this->elements,
            /** @param T $current */
            static fn ($current): bool => ! Equal::areEquals($current, $element),
        ));

        if (count($remaining) === count($this->elements)) {
            return $this;
        }

        $list           = clone $this;
        $list->elements = $remaining;

        return $list;
    }

    /** @return ArraySequence<T> */
    public function clear(): ArraySequence
    {
        return new ArraySequence();
    }

    /**
     * @param Closure(T): bool $predicate
     *
     * @return ArraySequence<T>
     */
    #[Override]
    public function filter(Closure $predicate): ArraySequence
    {
        $list           = clone $this;
        $list->elements = array_values(array_filter($this->elements, $predicate));

        return $list;
    }

    /**
     * @param Closure(T): U $mapper
     *
     * @return ArraySequence<U>
     *
     * @template U
     */
    #[Override]
    public function map(Closure $mapper): ArraySequence
    {
        return new ArraySequence(...array_map($mapper, $this->elements));
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
        return array_reduce($this->elements, $reducer, $initial);
    }

    /** @return array<int, T> */
    public function toArray(): array
    {
        return $this->elements;
    }

    public function count(): int
    {
        return count($this->elements);
    }

    /** @return Iterator<int, T> */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->elements);
    }

    /** @return T */
    public function getAtIndex(int $index)
    {
        $this->checkBounds($index);

        return $this->elements[$index];
    }

    /**
     * @param T $element
     *
     * @return ArraySequence<T>
     */
    public function setAtIndex(int $index, $element): ArraySequence
    {
        $this->checkBounds($index);

        $list                   = clone $this;
        $list->elements[$index] = $element;

        return $list;
    }

    /** @return ArraySequence<T> */
    public function removeAtIndex(int $index): ArraySequence
    {
        $this->checkBounds($index);

        $list = clone $this;
        unset($list->elements[$index]);
        $list->elements = array_values($list->elements);

        return $list;
    }

    /** @param T $element */
    public function search($element): int
    {
        return (new LinearSearch())->search($this->elements, $element);
    }

    /**
     * @param Comparator<T> $comparator
     *
     * @return ArraySequence<T>
     */
    public function sort(Comparator $comparator): ArraySequence
    {
        $list = clone $this;

        usort($list->elements, $comparator->compare(...));

        return $list;
    }

    /** @param T $element */
    public function contains($element): bool
    {
        return $this->search($element) >= 0;
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function equals(object $object): bool
    {
        if (! $object instanceof ArraySequence) {
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

    private function checkBounds(int $index): void
    {
        if ($index < 0 || $index >= $this->count()) {
            throw new IndexOutOfBounds($index, $this->count());
        }
    }
}
