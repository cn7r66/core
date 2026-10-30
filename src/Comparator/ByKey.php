<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Comparator;

use Closure;
use Override;

/**
 * Orders values by a key taken from each of them, in the order of the given comparator.
 *
 * @template T
 * @template K
 * @template-implements Comparator<T>
 */
final class ByKey implements Comparator
{
    /**
     * @param Closure(T): K $key
     * @param Comparator<K> $order
     */
    public function __construct(private Closure $key, private Comparator $order)
    {
    }

    /**
     * @param T $first
     * @param T $second
     */
    #[Override]
    public function compare($first, $second): int
    {
        return $this->order->compare(
            ($this->key)($first),
            ($this->key)($second),
        );
    }
}
