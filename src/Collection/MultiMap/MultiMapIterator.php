<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Collection\MultiMap;

use Iterator;
use Vivarium\Collection\Collection;

use function assert;

/**
 * @template K
 * @template V
 * @template-implements Iterator<K, V>
 */
final class MultiMapIterator implements Iterator
{
    /** @var Iterator<int, V>|null */
    private Iterator|null $iterator = null;

    /** @param Iterator<K, Collection<V>> $mapIterator */
    public function __construct(private Iterator $mapIterator)
    {
        $this->skipEmptyCollections();
    }

    /** @return V */
    public function current(): mixed
    {
        assert($this->iterator !== null);

        return $this->iterator->current();
    }

    public function next(): void
    {
        assert($this->iterator !== null);

        $this->iterator->next();
        if ($this->iterator->valid()) {
            return;
        }

        $this->mapIterator->next();
        $this->skipEmptyCollections();
    }

    /** @return K */
    public function key(): mixed
    {
        return $this->mapIterator->key();
    }

    public function valid(): bool
    {
        return $this->iterator !== null && $this->iterator->valid();
    }

    public function rewind(): void
    {
        $this->mapIterator->rewind();
        $this->skipEmptyCollections();
    }

    private function skipEmptyCollections(): void
    {
        while ($this->mapIterator->valid()) {
            $this->iterator = $this->mapIterator->current()->getIterator();
            $this->iterator->rewind();
            if ($this->iterator->valid()) {
                return;
            }

            $this->mapIterator->next();
        }

        $this->iterator = null;
    }
}
