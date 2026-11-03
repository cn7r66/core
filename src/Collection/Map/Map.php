<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Map;

use Closure;
use Countable;
use Iterator;
use IteratorAggregate;
use Vivarium\Collection\Pair\Pair;
use Vivarium\Equality\Equality;

/**
 * @template K
 * @template V
 * @template-extends IteratorAggregate<K, V>
 */
interface Map extends Countable, IteratorAggregate, Equality
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
     * @return V
     */
    public function get($key);

    /**
     * @param K $key
     *
     * @return self<K, V>
     */
    public function remove($key): self;

    /** @param K $key */
    public function containsKey($key): bool;

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
     * The pairs $predicate accepts, in iteration order.
     *
     * @param Closure(V, K): bool $predicate
     *
     * @return self<K, V>
     */
    public function filter(Closure $predicate): self;

    /**
     * Every value through $mapper, in iteration order; keys stay as they are.
     *
     * @param Closure(V, K): U $mapper
     *
     * @return self<K, U>
     *
     * @template U
     */
    public function map(Closure $mapper): self;

    /**
     * Folds the pairs in iteration order, starting from $initial.
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
