<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Collection\MultiMap;

use Closure;
use Countable;
use Iterator;
use IteratorAggregate;
use Vivarium\Collection\Collection;
use Vivarium\Collection\Pair\Pair;
use Vivarium\Equality\Equality;

/**
 * @template K
 * @template V
 * @template-extends IteratorAggregate<K, V>
 */
interface MultiMap extends Countable, IteratorAggregate, Equality
{
    /**
     * @param K $key
     * @param V $value
     *
     * @return self<K, V>
     */
    public function put($key, $value): self;

    /**
     * @param K $key
     *
     * @return Collection<V>
     */
    public function get($key): Collection;

    /**
     * @param K $key
     * @param V $value
     *
     * @return self<K, V>
     */
    public function remove($key, $value): self;

    /**
     * @param K $key
     *
     * @return self<K, V>
     */
    public function removeAll($key): self;

    /** @param K $key */
    public function containsKey($key): bool;

    /**
     * @param K $key
     * @param V $value
     */
    public function containsKeyValue($key, $value): bool;

    /** @param mixed $value */
    public function containsValue($value): bool;

    /** @return array<int, V> */
    public function values(): array;

    /** @return array<int, K> */
    public function keys(): array;

    /** @return array<int, Pair<K, V>> */
    public function pairs(): array;

    public function isEmpty(): bool;

    /** @return self<K, V> */
    public function clear(): self;

    /**
     * The values $predicate accepts, in iteration order.
     *
     * @param Closure(V, K): bool $predicate
     *
     * @return self<K, V>
     */
    public function filter(Closure $predicate): self;

    /**
     * Every value through $mapper, in iteration order, into buckets made by $factory; keys stay as they are.
     *
     * @param Closure(V, K): U         $mapper
     * @param Closure(): Collection<U> $factory
     *
     * @return self<K, U>
     *
     * @template U
     */
    public function map(Closure $mapper, Closure $factory): self;

    /**
     * Folds the values in iteration order, starting from $initial.
     *
     * @param Closure(R, V, K): R $reducer
     * @param R                   $initial
     *
     * @return R
     *
     * @template R
     */
    public function reduce(Closure $reducer, $initial);

    /** @return Iterator<K, V> */
    public function getIterator(): Iterator;

    public function count(): int;
}
