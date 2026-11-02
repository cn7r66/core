<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Set;

use Closure;
use Override;
use Vivarium\Collection\Collection;

/**
 * @template T
 * @template-extends Collection<T>
 */
interface Set extends Collection
{
    /** @return self<T> */
    public function clear(): self;

    /**
     * @param Closure(T): bool $predicate
     *
     * @return self<T>
     */
    #[Override]
    public function filter(Closure $predicate): self;

    /**
     * @param Closure(T): U $mapper
     *
     * @return self<U>
     *
     * @template U
     */
    #[Override]
    public function map(Closure $mapper): self;

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

    /**
     * @param Set<T> $set
     *
     * @return self<T>
     */
    public function union(Set $set): self;

    /**
     * @param Set<T> $set
     *
     * @return self<T>
     */
    public function intersection(Set $set): self;

    /**
     * @param Set<T> $set
     *
     * @return self<T>
     */
    public function difference(Set $set): self;

    /** @param Set<T> $set */
    public function isSubsetOf(Set $set): bool;
}
