<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Comparator;

use Override;
use Vivarium\Assertion\Var\IsNumber;

use function is_nan;

/**
 * A total order over numbers, without tolerance. NAN comes after every number and is
 * equivalent to itself. An int and a float compare as PHP's <=> does.
 *
 * @template-implements Comparator<int|float>
 */
final class ByNumber implements Comparator
{
    private IsNumber $isNumber;

    public function __construct()
    {
        $this->isNumber = new IsNumber();
    }

    /**
     * @param int|float $first
     * @param int|float $second
     */
    #[Override]
    public function compare($first, $second): int
    {
        $this->isNumber->assert($first);
        $this->isNumber->assert($second);

        if (is_nan($first) || is_nan($second)) {
            return is_nan($first) <=> is_nan($second);
        }

        return $first <=> $second;
    }
}
