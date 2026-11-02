<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection;

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

    /** @return array<int, T> */
    public function toArray(): array;

    /** @return Iterator<int, T> */
    public function getIterator(): Iterator;

    public function count(): int;
}
