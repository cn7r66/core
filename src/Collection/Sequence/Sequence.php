<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Sequence;

use Closure;
use Override;
use Vivarium\Collection\Collection;
use Vivarium\Comparator\Comparator;

/**
 * @template T
 * @template-extends Collection<T>
 */
interface Sequence extends Collection
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

    /**
     * @param T $element
     *
     * @return self<T>
     */
    public function setAtIndex(int $index, $element): self;

    /** @return self<T> */
    public function removeAtIndex(int $index): self;

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

    /** @return T */
    public function getAtIndex(int $index);

    /** @param T $element */
    public function search($element): int;

    /**
     * @param Comparator<T> $comparator
     *
     * @return self<T>
     */
    public function sort(Comparator $comparator): self;
}
