<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Collection\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Collection\Util\BinarySearch;
use Vivarium\Collection\Util\Vector;
use Vivarium\Comparator\IntegerComparator;

#[CoversClass(Vector::class)]
final class VectorTest extends TestCase
{
    public function testPutInPlace(): void
    {
        $binary = new BinarySearch(new IntegerComparator());

        $arr = [1, 2, 3, 4, 5];

        static::assertSame([1, 2, 3, 4, 5, 7], Vector::putInPlace($arr, 7, $binary));
        static::assertSame([0, 1, 2, 3, 4, 5], Vector::putInPlace($arr, 0, $binary));
        static::assertSame([1, 2, 3, 4, 5], Vector::putInPlace($arr, 1, $binary));
        static::assertSame([1, 2, 3, 4, 5], Vector::putInPlace($arr, 3, $binary));
    }

    public function testPutAtPlace(): void
    {
        $binary = new BinarySearch(new IntegerComparator());

        $arr = [1, 2, 3, 4, 5];

        static::assertSame([1, 2, 3, 4, 5, 7], Vector::putAtPlace($arr, 7, $binary));
        static::assertSame([1, 1, 2, 3, 4, 5], Vector::putAtPlace($arr, 1, $binary));
        static::assertSame([1, 2, 3, 3, 4, 5], Vector::putAtPlace($arr, 3, $binary));
        static::assertSame([1, 2, 3, 4, 5, 5], Vector::putAtPlace($arr, 5, $binary));
    }

    public function testPutAtIndex(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('Expected number to be in closed range [0, 5]. Got 6.');

        $arr = [3, 6, 1, 2, 5];

        static::assertSame([3, 6, 7, 1, 2, 5], Vector::putAtIndex($arr, 7, 2));
        static::assertSame([1, 3, 6, 1, 2, 5], Vector::putAtIndex($arr, 1, 0));
        static::assertSame([3, 6, 1, 2, 5, 8], Vector::putAtIndex($arr, 8, 5));

        Vector::putAtIndex($arr, 8, 6);
    }

    public function testLinearSearch(): void
    {
        $arr = [1, 2, 4, 5, 7, 8];

        static::assertSame(0, Vector::linearSearch($arr, 1));
        static::assertSame(5, Vector::linearSearch($arr, 8));
        static::assertSame(2, Vector::linearSearch($arr, 4));
        static::assertSame(-7, Vector::linearSearch($arr, 10));
    }

    public function testLinearContains(): void
    {
        $arr = [1, 2, 4, 5, 7, 8];

        static::assertTrue(Vector::linearContains($arr, 1));
        static::assertTrue(Vector::linearContains($arr, 8));
        static::assertTrue(Vector::linearContains($arr, 4));
        static::assertFalse(Vector::linearContains($arr, 10));
    }

    public function testBinarySearch(): void
    {
        $comparator = new IntegerComparator();
        $arr        = [1, 2, 4, 5, 7, 8];

        static::assertSame(0, Vector::binarySearch($arr, 1, $comparator));
        static::assertSame(5, Vector::binarySearch($arr, 8, $comparator));
        static::assertSame(2, Vector::binarySearch($arr, 4, $comparator));
        static::assertSame(-3, Vector::binarySearch($arr, 3, $comparator));
    }

    public function testBinaryContains(): void
    {
        $comparator = new IntegerComparator();
        $arr        = [1, 2, 4, 5, 7, 8];

        static::assertTrue(Vector::binaryContains($arr, 1, $comparator));
        static::assertTrue(Vector::binaryContains($arr, 8, $comparator));
        static::assertTrue(Vector::binaryContains($arr, 4, $comparator));
        static::assertFalse(Vector::binaryContains($arr, 10, $comparator));
    }
}
