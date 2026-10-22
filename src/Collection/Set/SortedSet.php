<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Set;

use Iterator;
use Vivarium\Collection\Map\Map;
use Vivarium\Collection\Map\SortedMap;
use Vivarium\Comparator\Comparator;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;

use function array_fill;
use function count;

/**
 * @template T
 * @template-implements Set<T>
 */
final class SortedSet implements Set
{
    private const int PLACEHOLDER = 1;

    private const int START = 0;

    /** @var Map<T, int>*/
    private Map $map;

    /**
     * @param Comparator<T> $comparator
     * @param T             ...$elements
     */
    public function __construct(private Comparator $comparator, ...$elements)
    {
        $placeholders = array_fill(self::START, count($elements), self::PLACEHOLDER);

        $this->map = SortedMap::fromKeyValue($comparator, $elements, $placeholders);
    }

    /**
     * @param Comparator<K> $comparator
     * @param array<K>      $elements
     *
     * @return SortedSet<K>
     *
     * @template K
     */
    public static function fromArray(Comparator $comparator, array $elements): SortedSet
    {
        return new SortedSet($comparator, ...$elements);
    }

    /**
     * @param T $element
     *
     * @return SortedSet<T>
     */
    public function add($element): SortedSet
    {
        if ($this->contains($element)) {
            return $this;
        }

        $set      = clone $this;
        $set->map = $set->map->put($element, self::PLACEHOLDER);

        return $set;
    }

    /**
     * @param T $element
     *
     * @return SortedSet<T>
     */
    public function remove($element): SortedSet
    {
        $set      = clone $this;
        $set->map = $set->map->remove($element);

        return $set;
    }

    /** @return SortedSet<T> */
    public function clear(): SortedSet
    {
        return new SortedSet($this->comparator);
    }

    /** @return array<int, T> */
    public function toArray(): array
    {
        return $this->map->keys();
    }

    /** @return Iterator<int, T> */
    public function getIterator(): Iterator
    {
        return new SetIterator($this->map->pairs());
    }

    public function count(): int
    {
        return $this->map->count();
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /** @param T $element */
    public function contains($element): bool
    {
        return $this->map->containsKey($element);
    }

    /**
     * @param Set<T> $set
     *
     * @return SortedSet<T>
     */
    public function union(Set $set): SortedSet
    {
        $sortedSet = clone $this;
        foreach ($set as $element) {
            $sortedSet = $sortedSet->add($element);
        }

        return $sortedSet;
    }

    /**
     * @param Set<T> $set
     *
     * @return SortedSet<T>
     */
    public function intersection(Set $set): SortedSet
    {
        $intersection = new SortedSet($this->comparator);
        foreach ($set as $element) {
            if (! $this->contains($element)) {
                continue;
            }

            $intersection = $intersection->add($element);
        }

        return $intersection;
    }

    /**
     * @param Set<T> $set
     *
     * @return SortedSet<T>
     */
    public function difference(Set $set): SortedSet
    {
        $difference = new SortedSet($this->comparator);
        foreach ($this as $element) {
            if ($set->contains($element)) {
                continue;
            }

            $difference = $difference->add($element);
        }

        return $difference;
    }

    /** @param Set<T> $set */
    public function isSubsetOf(Set $set): bool
    {
        foreach ($this as $element) {
            if (! $set->contains($element)) {
                return false;
            }
        }

        return true;
    }

    public function equals(object $object): bool
    {
        if (! $object instanceof SortedSet) {
            return false;
        }

        if ($object === $this) {
            return true;
        }

        return (new EqualsBuilder())
            ->append($this->map, $object->map)
            ->isEquals();
    }

    public function hash(): string
    {
        return (new HashBuilder())
            ->append($this->map)
            ->getHashCode();
    }
}
