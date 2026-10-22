<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Util;

use function count;
use function intval;

/**
 * @template T
 * @template V
 * @implements SearchAlgorithm<T, V>
 */
final class BinarySearch implements SearchAlgorithm
{
    private const int LEFT_HALF = -1;

    private const int RIGHT_HALF = 1;

    /** @var callable(T, V): int */
    private $comparator;

    /** @param callable(T, V): int $comparator */
    public function __construct(callable $comparator)
    {
        $this->comparator = $comparator;
    }

    /**
     * @param array<int, T> $array
     * @param V             $element
     */
    public function search(array $array, $element): int
    {
        return $this->searchRecursive($array, $element, 0, count($array));
    }

    /**
     * @param array<int, T> $array
     * @param V             $element
     */
    public function contains(array $array, $element): bool
    {
        return $this->search($array, $element) >= 0;
    }

    /**
     * @param T[] $array
     * @param V   $element
     */
    private function searchRecursive(array $array, $element, int $start, int $end): int
    {
        if ($start < $end) {
            $middle = intval(($start + $end) / 2);
            switch ($this->sign($element, $array[$middle])) {
                case self::LEFT_HALF:
                    return $this->searchRecursive($array, $element, $start, $middle);

                case self::RIGHT_HALF:
                    return $this->searchRecursive($array, $element, $middle + 1, $end);

                default:
                    return $middle;
            }
        }

        return -($start + 1);
    }

    /**
     * @param V $element
     * @param T $current
     */
    private function sign($element, $current): int
    {
        $sign = ($this->comparator)($current, $element);
        if ($sign === 0) {
            return $sign;
        }

        return $sign >= 1 ? -1 : 1;
    }
}
