<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Util;

/**
 * @template T
 * @template V
 */
interface SearchAlgorithm
{
    /**
     * The index of an element matching $element, or -(insertion point + 1) when none does: the
     * insertion point is where $element would go (count($array) when the array has no order), so
     * every miss is negative and -($result + 1) recovers it.
     *
     * @param array<int, T> $array
     * @param V             $element
     */
    public function search(array $array, $element): int;

    /**
     * @param array<int, T> $array
     * @param V             $element
     */
    public function contains(array $array, $element): bool;
}
