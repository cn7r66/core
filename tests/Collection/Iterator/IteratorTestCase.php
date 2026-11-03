<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Collection\Iterator;

use Iterator;
use PHPUnit\Framework\TestCase;
use Vivarium\Equality\Equal;

abstract class IteratorTestCase extends TestCase
{
    /**
     * @param Iterator<K, V>    $iterator
     * @param list<array{K, V}> $expected
     *
     * @template K
     * @template V
     */
    protected function doTest(Iterator $iterator, array $expected): void
    {
        $this->checkPairs($expected, $this->pairsOf($iterator));

        $iterator->rewind();

        $this->checkPairs($expected, $this->pairsOf($iterator));
    }

    /**
     * @param Iterator<K, V> $iterator
     *
     * @return list<array{K, V}>
     *
     * @template K
     * @template V
     */
    private function pairsOf(Iterator $iterator): array
    {
        $pairs = [];
        while ($iterator->valid()) {
            $pairs[] = [$iterator->key(), $iterator->current()];
            $iterator->next();
        }

        return $pairs;
    }

    /**
     * @param list<array{K, V}> $expected
     * @param list<array{K, V}> $pairs
     *
     * @template K
     * @template V
     */
    private function checkPairs(array $expected, array $pairs): void
    {
        static::assertSameSize($expected, $pairs);
        foreach ($expected as $pair) {
            static::assertTrue($this->contains($pair, $pairs));
        }
    }

    /**
     * @param array{K, V}       $needle
     * @param list<array{K, V}> $pairs
     *
     * @template K
     * @template V
     */
    private function contains(array $needle, array $pairs): bool
    {
        foreach ($pairs as $pair) {
            if (Equal::areEquals($needle[0], $pair[0]) && Equal::areEquals($needle[1], $pair[1])) {
                return true;
            }
        }

        return false;
    }
}
