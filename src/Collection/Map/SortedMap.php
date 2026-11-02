<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Map;

use Vivarium\Assertion\Comparison\IsEqualTo;
use Vivarium\Collection\Exception\KeyNotFound;
use Vivarium\Collection\Pair\Pair;
use Vivarium\Collection\Util\BinarySearch;
use Vivarium\Collection\Util\SearchAlgorithm;
use Vivarium\Collection\Util\Vector;
use Vivarium\Comparator\Comparator;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;

use function array_map;
use function array_values;
use function count;

/**
 * @template K
 * @template V
 * @template-implements Map<K, V>
 */
class SortedMap implements Map
{
    /** @var array<int, Pair<K, V>> */
    private array $pairs;

    /**
     * @param Comparator<K> $comparator
     * @param Pair<K, V>    ...$pairs
     */
    public function __construct(private Comparator $comparator, Pair ...$pairs)
    {
        $this->pairs = [];

        foreach ($pairs as $pair) {
            $this->pairs = Vector::put(
                $this->pairs,
                $pair,
                $this->searchByPair(),
            );
        }
    }

    /**
     * @param Comparator<K0> $comparator
     * @param K0[]           $keys
     * @param V0[]           $values
     *
     * @return SortedMap<K0, V0>
     *
     * @template K0
     * @template V0
     */
    public static function fromKeyValue(Comparator $comparator, array $keys, array $values): SortedMap
    {
        (new IsEqualTo(count($keys)))
            ->assert(count($values), 'Expected {compare} values, one per key. Got {value}.');

        return new SortedMap($comparator, ...array_map(
            /**
             * @param K0 $key
             * @param V0 $value
             *
             * @return Pair<K0, V0>
             */
            static fn ($key, $value): Pair => new Pair($key, $value),
            $keys,
            $values,
        ));
    }

    /**
     * @param K $key
     * @param V $value
     *
     * @return SortedMap<K, V>
     */
    public function put($key, $value): SortedMap
    {
        $map        = clone $this;
        $map->pairs = Vector::put(
            $this->pairs,
            new Pair($key, $value),
            $this->searchByPair(),
        );

        return $map;
    }

    /**
     * @param K $key
     *
     * @return V
     */
    public function get($key)
    {
        $index = $this->searchByKey()->search($this->pairs, $key);
        if ($index < 0) {
            throw new KeyNotFound($key);
        }

        return $this->pairs[$index]->getValue();
    }

    /**
     * @param K $key
     *
     * @return SortedMap<K, V>
     */
    public function remove($key): SortedMap
    {
        $index = $this->searchByKey()->search($this->pairs, $key);
        if ($index < 0) {
            return $this;
        }

        $map = clone $this;
        unset($map->pairs[$index]);
        $map->pairs = array_values($map->pairs);

        return $map;
    }

    /** @param K $key */
    public function containsKey($key): bool
    {
        return $this->searchByKey()
                    ->contains(
                        $this->pairs,
                        $key,
                    );
    }

    /** @param mixed $value */
    public function containsValue($value): bool
    {
        foreach ($this->pairs as $pair) {
            if (
                (new EqualsBuilder())
                ->append($value, $pair->getValue())
                ->isEquals()
            ) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, V> */
    public function values(): array
    {
        $values = [];
        foreach ($this->pairs as $pair) {
            $values[] = $pair->getValue();
        }

        return $values;
    }

    /** @return array<int, K> */
    public function keys(): array
    {
        $keys = [];
        foreach ($this->pairs as $pair) {
            $keys[] = $pair->getKey();
        }

        return $keys;
    }

    /** @return array<int, Pair<K, V>> */
    public function pairs(): array
    {
        return $this->pairs;
    }

    /** @return SortedMap<K, V> */
    public function clear(): SortedMap
    {
        return new SortedMap($this->comparator);
    }

    public function count(): int
    {
        return count($this->pairs());
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /** @return MapIterator<K, V> */
    public function getIterator(): MapIterator
    {
        return new MapIterator($this->pairs());
    }

    public function equals(object $object): bool
    {
        if (! $object instanceof SortedMap) {
            return false;
        }

        if ($object === $this) {
            return true;
        }

        return (new EqualsBuilder())
            ->append($this->pairs, $object->pairs)
            ->isEquals();
    }

    public function hash(): string
    {
        return (new HashBuilder())
            ->append($this->pairs)
            ->getHashCode();
    }

    /** @return SearchAlgorithm<Pair<K, V>, Pair<K, V>> */
    private function searchByPair(): SearchAlgorithm
    {
        return new BinarySearch(
            /**
             * @param Pair<K, V> $pair1
             * @param Pair<K, V> $pair2
             */
            fn (Pair $pair1, Pair $pair2): int => $this->comparator->compare($pair1->getKey(), $pair2->getKey()),
        );
    }

    /** @return SearchAlgorithm<Pair<K, V>, K> */
    private function searchByKey(): SearchAlgorithm
    {
        return new BinarySearch(
            /**
             * @param Pair<K, V> $pair
             * @param K          $key
             */
            fn (Pair $pair, $key): int => $this->comparator->compare($pair->getKey(), $key),
        );
    }
}
