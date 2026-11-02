<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Set;

use ArrayIterator;
use Iterator;
use Vivarium\Collection\Pair\Pair;

/**
 * @template T
 * @template-implements Iterator<int, T>
 */
final class SetIterator implements Iterator
{
    /** @var Iterator<int, Pair<T, int>> */
    private Iterator $iterator;

    private int $index;

    /** @param array<int, Pair<T, int>> $pairs */
    public function __construct(array $pairs)
    {
        $this->iterator = new ArrayIterator($pairs);
        $this->index    = 0;
    }

    /** @return T */
    public function current(): mixed
    {
        return $this->iterator->current()->getKey();
    }

    public function next(): void
    {
        $this->iterator->next();
        ++$this->index;
    }

    public function key(): int
    {
        return $this->index;
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function rewind(): void
    {
        $this->iterator->rewind();
        $this->index = 0;
    }
}
