<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Float;

use Vivarium\Assertion\Numeric\IsInRightOpenRange;

use function abs;
use function min;

use const INF;
use const PHP_FLOAT_EPSILON;
use const PHP_FLOAT_MAX;
use const PHP_FLOAT_MIN;

/**
 * Two floats are nearly equal when their difference, relative to the sum of their magnitudes,
 * is at most epsilon. A difference below PHP_FLOAT_MIN is compared absolutely, to
 * epsilon * PHP_FLOAT_MIN, so values around zero can be nearly equal.
 *
 * Not transitive, so never an equality: no hash or ordering can agree with it.
 * NAN is never nearly equal to anything, itself included; an infinity only to itself.
 * An epsilon of 0.0 means exact equality.
 */
final class NearlyEquals
{
    public function __construct(private float $epsilon = PHP_FLOAT_EPSILON)
    {
        (new IsInRightOpenRange(0.0, INF))
            ->assert($epsilon, 'Expected epsilon to be finite and non-negative. Got {value}.');
    }

    public function equals(float $first, float $second): bool
    {
        if ($first === $second) {
            return true;
        }

        $diff = abs($first - $second);
        if ($diff < PHP_FLOAT_MIN) {
            return $diff <= $this->epsilon * PHP_FLOAT_MIN;
        }

        return $diff / min(abs($first) + abs($second), PHP_FLOAT_MAX) <= $this->epsilon;
    }
}
