<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Set;

use Iterator;
use Vivarium\Collection\Map\HashMap;
use Vivarium\Collection\Map\Map;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;

use function array_fill;
use function array_values;
use function count;

/**
 * @template T
 * @template-implements Set<T>
 */
final class HashSet implements Set
{
    private const int PLACEHOLDER = 1;

    private const int START = 0;

    /** @var Map<T, int> */
    private Map $map;

    /** @param T ...$elements */
    public function __construct(...$elements)
    {
        $placeholders = array_fill(self::START, count($elements), self::PLACEHOLDER);

        $this->map = HashMap::fromKeyValue($elements, $placeholders);
    }

    /**
     * @param array<K> $elements
     *
     * @return HashSet<K>
     *
     * @template K
     */
    public static function fromArray(array $elements): HashSet
    {
        return new HashSet(...array_values($elements));
    }

    /** @return HashSet<T> */
    public function clear(): HashSet
    {
        return new HashSet();
    }

    /**
     * @param T $element
     *
     * @return HashSet<T>
     */
    public function add($element): HashSet
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
     * @return HashSet<T>
     */
    public function remove($element): HashSet
    {
        $set      = clone $this;
        $set->map = $set->map->remove($element);

        return $set;
    }

    /**
     * @param Set<T> $set
     *
     * @return HashSet<T>
     */
    public function union(Set $set): HashSet
    {
        $union = clone $this;
        foreach ($set as $value) {
            $union = $union->add($value);
        }

        return $union;
    }

    /**
     * @param Set<T> $set
     *
     * @return HashSet<T>
     */
    public function intersection(Set $set): HashSet
    {
        $elements = [];
        foreach ($this as $element) {
            if (! $set->contains($element)) {
                continue;
            }

            $elements[] = $element;
        }

        return new HashSet(...$elements);
    }

    /**
     * @param Set<T> $set
     *
     * @return HashSet<T>
     */
    public function difference(Set $set): HashSet
    {
        $elements = [];
        foreach ($this as $element) {
            if ($set->contains($element)) {
                continue;
            }

            $elements[] = $element;
        }

        return self::fromArray($elements);
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

    /** @param T $element */
    public function contains($element): bool
    {
        return $this->map->containsKey($element);
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

    public function equals(object $object): bool
    {
        if (! $object instanceof HashSet) {
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
