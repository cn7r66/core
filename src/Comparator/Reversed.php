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
 * The opposite of the order it wraps.
 *
 * @template T
 * @template-implements Comparator<T>
 */
final class Reversed implements Comparator
{
    /** @param Comparator<T> $comparator */
    public function __construct(private Comparator $comparator)
    {
    }

    /**
     * @param T $first
     * @param T $second
     */
    #[Override]
    public function compare($first, $second): int
    {
        return $this->comparator->compare($second, $first);
    }
}
