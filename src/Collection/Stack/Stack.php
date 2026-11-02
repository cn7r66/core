<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Stack;

use Closure;
use Override;
use Vivarium\Collection\Collection;

/**
 * @template T
 * @template-extends Collection<T>
 */
interface Stack extends Collection
{
    /**
     * @param T $element
     *
     * @return self<T>
     */
    public function push($element): self;

    /** @return self<T> */
    public function pop(): self;

    /** @return T */
    public function peek();

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
}
