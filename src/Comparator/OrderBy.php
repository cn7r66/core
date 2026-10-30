<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Comparator;

use Override;

/**
 * Each comparator breaks the ties of the one before it, as SQL's ORDER BY a, b: the first
 * non-zero result decides, and the values are equivalent when every comparator ties.
 *
 * @template T
 * @template-implements Comparator<T>
 */
final class OrderBy implements Comparator
{
    /** @var array<Comparator<T>> */
    private array $comparators;

    /**
     * @param Comparator<T> $first
     * @param Comparator<T> ...$rest
     */
    public function __construct(Comparator $first, Comparator ...$rest)
    {
        $this->comparators = [$first, ...$rest];
    }

    /**
     * @param T $first
     * @param T $second
     */
    #[Override]
    public function compare($first, $second): int
    {
        foreach ($this->comparators as $comparator) {
            $sign = $comparator->compare($first, $second);
            if ($sign !== 0) {
                return $sign;
            }
        }

        return 0;
    }
}
