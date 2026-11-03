<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Map;

use Closure;
use Override;
use Vivarium\Assertion\Comparison\IsEqualTo;
use Vivarium\Collection\Exception\KeyNotFound;
use Vivarium\Collection\Pair\Pair;
use Vivarium\Collection\Util\KeyHash;
use Vivarium\Collection\Util\LinearSearch;
use Vivarium\Collection\Util\Vector;
use Vivarium\Comparator\ByByte;
use Vivarium\Equality\Equal;
use Vivarium\Equality\HashBuilder;

use function array_all;
use function array_filter;
use function array_map;
use function array_values;
use function count;
use function usort;

/**
 * @template K
 * @template V
 * @template-implements Map<K, V>
 */
final class HashMap implements Map
{
    /** @var array<array-key, array<int, Pair<K, V>>> */
    private array $pairs;

    /** @param Pair<K, V> ...$pairs */
    public function __construct(Pair ...$pairs)
    {
        $this->pairs = [];
        foreach ($pairs as $pair) {
            $hash = KeyHash::hash($pair->getKey());
            if (! isset($this->pairs[$hash])) {
                $this->pairs[$hash] = [];
            }

            $this->pairs[$hash] = Vector::put(
                $this->pairs[$hash],
                $pair,
                $this->searchByPair(),
            );
        }
    }

    /**
     * @param K0[] $keys
     * @param V0[] $values
     *
     * @return HashMap<K0, V0>
     *
     * @template K0
     * @template V0
     */
    public static function fromKeyValue(array $keys, array $values): HashMap
    {
        (new IsEqualTo(count($keys)))
            ->assert(count($values), 'Expected {compare} values, one per key. Got {value}.');

        return new HashMap(...array_map(
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
     * @return HashMap<K, V>
     */
    public function put($key, $value): HashMap
    {
        $map = clone $this;

        $hash = KeyHash::hash($key);
        if (! isset($map->pairs[$hash])) {
            $map->pairs[$hash] = [];
        }

        $map->pairs[$hash] = Vector::put(
            $map->pairs[$hash],
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
        $hash = KeyHash::hash($key);

        if (isset($this->pairs[$hash])) {
            $index = $this->searchByKey()->search($this->pairs[$hash], $key);
            if ($index >= 0) {
                return $this->pairs[$hash][$index]->getValue();
            }
        }

        throw new KeyNotFound($key);
    }

    /**
     * @param K $key
     *
     * @return HashMap<K, V>
     */
    public function remove($key): HashMap
    {
        $hash   = KeyHash::hash($key);
        $bucket = $this->pairs[$hash] ?? [];
        $index  = $this->searchByKey()->search($bucket, $key);
        if ($index < 0) {
            return $this;
        }

        unset($bucket[$index]);

        $map = clone $this;
        if ($bucket === []) {
            unset($map->pairs[$hash]);

            return $map;
        }

        $map->pairs[$hash] = array_values($bucket);

        return $map;
    }

    /** @return array<int, Pair<K, V>> */
    public function pairs(): array
    {
        $pairs = [];
        foreach ($this->pairs as $bucket) {
            foreach ($bucket as $pair) {
                $pairs[] = $pair;
            }
        }

        return $pairs;
    }

    /** @param mixed $value */
    public function containsValue($value): bool
    {
        foreach ($this->pairs as $bucket) {
            foreach ($bucket as $pair) {
                if (Equal::areEquals($value, $pair->getValue())) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return array<int, V> */
    public function values(): array
    {
        $values = [];
        foreach ($this->pairs as $bucket) {
            foreach ($bucket as $pair) {
                $values[] = $pair->getValue();
            }
        }

        return $values;
    }

    /** @return array<int, K> */
    public function keys(): array
    {
        $keys = [];
        foreach ($this->pairs as $bucket) {
            foreach ($bucket as $pair) {
                $keys[] = $pair->getKey();
            }
        }

        return $keys;
    }

    /** @param K $key */
    public function containsKey($key): bool
    {
        $hash = KeyHash::hash($key);

        return isset($this->pairs[$hash]) && $this->searchByKey()->contains($this->pairs[$hash], $key);
    }

    /** @return HashMap<K, V> */
    public function clear(): HashMap
    {
        $map        = clone $this;
        $map->pairs = [];

        return $map;
    }

    /**
     * @param Closure(V, K): bool $predicate
     *
     * @return HashMap<K, V>
     */
    #[Override]
    public function filter(Closure $predicate): HashMap
    {
        return new HashMap(...array_filter(
            $this->pairs(),
            /** @param Pair<K, V> $pair */
            static fn (Pair $pair): bool => $predicate($pair->getValue(), $pair->getKey()),
        ));
    }

    /**
     * @param Closure(V, K): U $mapper
     *
     * @return HashMap<K, U>
     *
     * @template U
     */
    #[Override]
    public function map(Closure $mapper): HashMap
    {
        return new HashMap(...array_map(
            /** @param Pair<K, V> $pair */
            static fn (Pair $pair): Pair => new Pair($pair->getKey(), $mapper($pair->getValue(), $pair->getKey())),
            $this->pairs(),
        ));
    }

    /**
     * @param Closure(R, V, K): R $reducer
     * @param R                   $initial
     *
     * @return R
     *
     * @template R
     */
    #[Override]
    public function reduce(Closure $reducer, $initial)
    {
        $carry = $initial;
        foreach ($this as $key => $value) {
            $carry = $reducer($carry, $value, $key);
        }

        return $carry;
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this->pairs as $bucket) {
            $count += count($bucket);
        }

        return $count;
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /** @return MapIterator<K, V> */
    public function getIterator(): MapIterator
    {
        return new MapIterator($this->pairs);
    }

    public function equals(object $object): bool
    {
        if (! $object instanceof HashMap) {
            return false;
        }

        if ($object === $this) {
            return true;
        }

        return $this->count() === $object->count() && array_all(
            $this->pairs(),
            /** @param Pair<K, V> $pair */
            static function (Pair $pair) use ($object): bool {
                return $object->containsKey($pair->getKey())
                    && Equal::areEquals(
                        $pair->getValue(),
                        $object->get($pair->getKey()),
                    );
            },
        );
    }

    /** Entry hashes in sorted order, so maps equal in content hash alike whatever their insertion order. */
    public function hash(): string
    {
        $entries = array_map(static fn (Pair $pair): string => $pair->hash(), $this->pairs());
        usort($entries, (new ByByte())->compare(...));

        return (new HashBuilder())
            ->append($entries)
            ->getHashCode();
    }

    /** @return LinearSearch<Pair<K, V>, Pair<K, V>> */
    private function searchByPair(): LinearSearch
    {
        return new LinearSearch(
            /**
             * @param Pair<K, V> $pair1
             * @param Pair<K, V> $pair2
             */
            static fn (Pair $pair1, Pair $pair2): bool => Equal::areEquals($pair1->getKey(), $pair2->getKey()),
        );
    }

    /** @return LinearSearch<Pair<K, V>, K> */
    private function searchByKey(): LinearSearch
    {
        return new LinearSearch(
            /**
             * @param Pair<K, V> $pair
             * @param K          $key
             */
            static fn (Pair $pair, $key): bool => Equal::areEquals($pair->getKey(), $key),
        );
    }
}
