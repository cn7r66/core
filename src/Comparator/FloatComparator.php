<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Comparator;

use Vivarium\Float\NearlyEquals;

use const PHP_FLOAT_EPSILON;

/** @template-implements Comparator<float> */
final class FloatComparator implements Comparator
{
    private NearlyEquals $nearlyEquals;

    public function __construct(float $epsilon = PHP_FLOAT_EPSILON)
    {
        $this->nearlyEquals = new NearlyEquals($epsilon);
    }

    /**
     * @param float $first
     * @param float $second
     */
    public function compare($first, $second): int
    {
        if ($this->nearlyEquals->equals($first, $second)) {
            return 0;
        }

        if ($first < $second) {
            return -1;
        }

        return 1;
    }

    /**
     * @param float $first
     * @param float $second
     */
    public function __invoke($first, $second): int
    {
        return $this->compare($first, $second);
    }
}
