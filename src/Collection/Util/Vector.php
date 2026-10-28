<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Util;

use Vivarium\Assertion\Numeric\IsInClosedRange;

use function abs;
use function array_merge;
use function array_slice;
use function count;

final class Vector
{
    /**
     * @param array<int, T>         $array
     * @param T                     $element
     * @param SearchAlgorithm<T, T> $algo
     *
     * @return array<int, T>
     *
     * @template T
     */
    public static function putInPlace(array $array, $element, SearchAlgorithm $algo): array
    {
        $index = $algo->search($array, $element);

        if ($index >= 0) {
            $array[$index] = $element;

            return $array;
        }

        $index = abs($index + 1);

        return static::putAtIndex($array, $element, $index);
    }

    /**
     * @param array<int, T>         $array
     * @param T                     $element
     * @param SearchAlgorithm<T, T> $algo
     *
     * @return array<int, T>
     *
     * @template T
     */
    public static function putAtPlace(array $array, $element, SearchAlgorithm $algo): array
    {
        $index = $algo->search($array, $element);

        $index = abs($index + 1);

        return static::putAtIndex($array, $element, $index);
    }

    /**
     * @param array<int, T> $array
     * @param T             $element
     *
     * @return array<int, T>
     *
     * @template T
     */
    public static function putAtIndex(array $array, $element, int $index): array
    {
        (new IsInClosedRange(0, count($array)))
            ->assert($index);

        $leftSlice  = array_slice($array, 0, $index);
        $rightSlice = array_slice($array, $index, count($array));

        return array_merge($leftSlice, [$element], $rightSlice);
    }

    /**
     * @param array<int, T>            $array
     * @param T                        $element
     * @param callable(T, T):bool|null $equals
     *
     * @template T
     */
    public static function linearSearch(array $array, $element, callable|null $equals = null): int
    {
        return (new LinearSearch($equals))
            ->search($array, $element);
    }

    /**
     * @param array<int, T>            $array
     * @param T                        $element
     * @param callable(T, T):bool|null $equals
     * @phpstan-param callable(T, T):bool|null $equals
     *
     * @template T
     */
    public static function linearContains(array $array, $element, callable|null $equals = null): bool
    {
        return static::linearSearch($array, $element, $equals) >= 0;
    }

    /**
     * @param array<int, T>      $array
     * @param V                  $element
     * @param callable(T, V):int $equals
     *
     * @template T
     * @template V
     */
    public static function binarySearch(array $array, $element, callable $equals): int
    {
        return (new BinarySearch($equals))
            ->search($array, $element);
    }

    /**
     * @param array<int, T>      $array
     * @param V                  $element
     * @param callable(T, V):int $equals
     * @phpstan-param callable(T, V):int $equals
     *
     * @template T
     * @template V
     */
    public static function binaryContains(array $array, $element, callable $equals): bool
    {
        return static::binarySearch($array, $element, $equals) >= 0;
    }
}
