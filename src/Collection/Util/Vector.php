<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Util;

use Vivarium\Assertion\Numeric\IsInClosedRange;

use function array_merge;
use function array_slice;
use function count;

final class Vector
{
    /**
     * Replaces the element equal to $element, or inserts $element where $search reports it
     * belongs.
     *
     * @param array<int, T>         $array
     * @param T                     $element
     * @param SearchAlgorithm<T, T> $search
     *
     * @return array<int, T>
     *
     * @template T
     */
    public static function put(array $array, $element, SearchAlgorithm $search): array
    {
        $index = $search->search($array, $element);
        if ($index >= 0) {
            $array[$index] = $element;

            return $array;
        }

        return self::insertAtIndex($array, $element, -($index + 1));
    }

    /**
     * Inserts $element at $index, shifting the following elements; nothing is replaced.
     *
     * @param array<int, T> $array
     * @param T             $element
     *
     * @return array<int, T>
     *
     * @template T
     */
    public static function insertAtIndex(array $array, $element, int $index): array
    {
        (new IsInClosedRange(0, count($array)))
            ->assert($index);

        return array_merge(array_slice($array, 0, $index), [$element], array_slice($array, $index));
    }
}
