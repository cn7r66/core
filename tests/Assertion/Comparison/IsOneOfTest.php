<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Comparison;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Comparison\IsOneOf;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Test\Equality\Stub\EqualityStub;

#[CoversClass(IsOneOf::class)]
final class IsOneOfTest extends TestCase
{
    /** @param array<scalar|object> $values */
    #[DataProvider('provideSuccess')]
    public function testAssert(mixed $value, array $values): void
    {
        static::expectNotToPerformAssertions();

        $oneOf = new IsOneOf($values);

        $oneOf->assert($value);
    }

    /** @param array<scalar|object> $values */
    #[DataProvider('provideFailure')]
    public function testAssertException(mixed $value, array $values, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsOneOf($values))
            ->assert($value);
    }

    /** @return array<array{0: scalar|object, 1:array<scalar|object>}> */
    public static function provideSuccess(): array
    {
        $stdClass = new stdClass();

        return [
            [1, [1, 5, 7, 42]],
            [5, [1, 5, 7, 42]],
            [7, [1, 5, 7, 42]],
            [42, [1, 5, 7, 42]],
            [new EqualityStub(), [new EqualityStub(), new EqualityStub()]],
            [$stdClass, [new stdClass(), $stdClass]],
        ];
    }

    /** @return array<array{0: int, 1:array<int>, 2:string}> */
    public static function provideFailure(): array
    {
        return [
            [27, [1, 5, 7, 42], 'Expected value to be one of the values provided. Got 27.'],
        ];
    }
}
