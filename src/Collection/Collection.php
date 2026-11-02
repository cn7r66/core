<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection;

use Closure;
use Countable;
use Iterator;
use IteratorAggregate;
use Vivarium\Equality\Equality;

/**
 * @template T
 * @template-extends IteratorAggregate<int, T>
 */
interface Collection extends Countable, IteratorAggregate, Equality
{
    /**
     * @param T $element
     *
     * @return self<T>
     */
    public function add($element): self;

    /**
     * @param T $element
     *
     * @return self<T>
     */
    public function remove($element): self;

    /** @param T $element */
    public function contains($element): bool;

    public function isEmpty(): bool;

    /** @return self<T> */
    public function clear(): self;

    /**
     * The elements $predicate accepts, in iteration order.
     *
     * @param Closure(T): bool $predicate
     *
     * @return self<T>
     */
    public function filter(Closure $predicate): self;

    /**
     * Every element through $mapper, in iteration order.
     *
     * @param Closure(T): U $mapper
     *
     * @return self<U>
     *
     * @template U
     */
    public function map(Closure $mapper): self;

    /**
     * Folds the elements in iteration order, starting from $initial.
     *
     * @param Closure(R, T): R $reducer
     * @param R                $initial
     *
     * @return R
     *
     * @template R
     */
    public function reduce(Closure $reducer, $initial);

    /** @return array<int, T> */
    public function toArray(): array;

    /** @return Iterator<int, T> */
    public function getIterator(): Iterator;

    public function count(): int;
}
