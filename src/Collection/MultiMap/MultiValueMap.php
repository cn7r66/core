<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Collection\MultiMap;

use Vivarium\Collection\Collection;
use Vivarium\Collection\Map\HashMap;
use Vivarium\Collection\Map\Map;
use Vivarium\Collection\Pair\Pair;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;

use function array_merge;

/**
 * @template K
 * @template V
 * @template-implements MultiMap<K, V>
 */
final class MultiValueMap implements MultiMap
{
    /** @var callable(): Collection<V> */
    private $factory;

    /** @var Map<K, Collection<V>> */
    private Map $map;

    /**
     * @param callable(): Collection<V> $factory
     * @param Pair<K, Collection<V>>    ...$collections
     */
    public function __construct(callable $factory, Pair ...$collections)
    {
        $this->factory = $factory;

        $map = new HashMap();

        foreach ($collections as $collection) {
            $map = $map->put(
                $collection->getKey(),
                $collection->getValue(),
            );
        }

        /** @phpstan-var Map<K, Collection<V>> $map */
        $this->map = $map;
    }

    /**
     * @param K $key
     * @param V $value
     *
     * @return MultiValueMap<K, V>
     */
    public function put($key, $value): MultiValueMap
    {
        $multimap = clone $this;

        $multimap->map = $multimap->map->put(
            $key,
            $multimap->get($key)
                     ->add($value),
        );

        return $multimap;
    }

    /**
     * @param K $key
     *
     * @return Collection<V>
     */
    public function get($key): Collection
    {
        return $this->map->containsKey($key) ?
            $this->map->get($key) : ($this->factory)();
    }

    /**
     * @param K $key
     * @param V $value
     *
     * @return self<K, V>
     */
    public function remove($key, $value): MultiMap
    {
        if (! $this->map->containsKey($key)) {
            return $this;
        }

        $values    = $this->get($key);
        $remaining = $values->remove($value);
        if ($remaining === $values) {
            return $this;
        }

        $multimap      = clone $this;
        $multimap->map = $multimap->map->put($key, $remaining);

        return $multimap;
    }

    /**
     * @param K $key
     *
     * @return self<K, V>
     */
    public function removeAll($key): MultiMap
    {
        if (! $this->map->containsKey($key)) {
            return $this;
        }

        $multimap      = clone $this;
        $multimap->map = $multimap->map->remove($key);

        return $multimap;
    }

    /** @param K $key */
    public function containsKey($key): bool
    {
        return $this->map->containsKey($key);
    }

    /**
     * @param K $key
     * @param V $value
     */
    public function containsKeyValue($key, $value): bool
    {
        return $this->get($key)
                    ->contains($value);
    }

    /** @param V $value */
    public function containsValue($value): bool
    {
        foreach ($this->map as $collection) {
            if ($collection->contains($value)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, V> */
    public function values(): array
    {
        /** @var array<int, V> $values */
        $values = [];
        foreach ($this->map as $collection) {
            $values = array_merge($values, $collection->toArray());
        }

        return $values;
    }

    /** @return array<int, K> */
    public function keys(): array
    {
        return $this->map->keys();
    }

    /** @return array<int, Pair<K, V>> */
    public function pairs(): array
    {
        $pairs = [];
        foreach ($this->map as $key => $collection) {
            foreach ($collection as $value) {
                $pairs[] = new Pair($key, $value);
            }
        }

        return $pairs;
    }

    public function isEmpty(): bool
    {
        foreach ($this->map as $collection) {
            if (! $collection->isEmpty()) {
                return false;
            }
        }

        return true;
    }

    /** @return MultiMap<K, V> */
    public function clear(): MultiMap
    {
        return new MultiValueMap($this->factory);
    }

    /** @return MultiMapIterator<K, V> */
    public function getIterator(): MultiMapIterator
    {
        return new MultiMapIterator($this->map->getIterator());
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this->map as $collection) {
            $count += $collection->count();
        }

        return $count;
    }

    public function equals(object $object): bool
    {
        if (! $object instanceof MultiValueMap) {
            return false;
        }

        if ($object === $this) {
            return true;
        }

        return (new EqualsBuilder())
            ->append(($this->factory)(), ($object->factory)())
            ->append($this->map, $object->map)
            ->isEquals();
    }

    public function hash(): string
    {
        return (new HashBuilder())
            ->append(($this->factory)())
            ->append($this->map)
            ->getHashCode();
    }
}
