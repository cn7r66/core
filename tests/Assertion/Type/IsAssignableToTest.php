<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Type\IsAssignableTo;

use function array_merge;

#[CoversClass(IsAssignableTo::class)]
final class IsAssignableToTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string $type, string $target): void
    {
        static::expectNotToPerformAssertions();

        (new IsAssignableTo($target))
            ->assert($type);
    }

    #[DataProvider('provideFailure')]
    public function testAssertException(string $type, string $target, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsAssignableTo($target))
            ->assert($type);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string $type, string $target): void
    {
        static::assertTrue(
            (new IsAssignableTo($target))($type),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string $type, string $target): void
    {
        static::assertFalse(
            (new IsAssignableTo($target))($type),
        );
    }

    /** @return array<array<string>> */
    public static function provideSuccess(): array
    {
        return array_merge(
            IsAssignableToClassTest::provideSuccess(),
            IsAssignableToIntersectionTest::provideSuccess(),
            IsAssignableToNullableTest::provideSuccess(),
            IsAssignableToPrimitiveTest::provideSuccess(),
            IsAssignableToUnionTest::provideSuccess(),
        );
    }

    /** @return array<array<string>> */
    public static function provideFailure(): array
    {
        return array_merge(
            IsAssignableToClassTest::provideFailure(),
            IsAssignableToIntersectionTest::provideFailure(),
            IsAssignableToNullableTest::provideFailure(),
            IsAssignableToPrimitiveTest::provideFailure(),
            IsAssignableToUnionTest::provideFailure(),
        );
    }
}
