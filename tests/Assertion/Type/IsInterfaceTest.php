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
use Traversable;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Type\IsInterface;
use Vivarium\Test\Assertion\Stub\Stub;
use Vivarium\Test\Assertion\Stub\StubClass;

#[CoversClass(IsInterface::class)]
final class IsInterfaceTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string $type): void
    {
        static::expectNotToPerformAssertions();

        (new IsInterface())
            ->assert($type);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(string|int $type, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsInterface())
            ->assert($type);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string $type): void
    {
        static::assertTrue(
            (new IsInterface())($type),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string $type): void
    {
        static::assertFalse(
            (new IsInterface())($type),
        );
    }

    /** @return array<array<string>> */
    public static function provideSuccess(): array
    {
        return [
            [Stub::class],
            [Traversable::class],
        ];
    }

    /** @return array<array<string>> */
    public static function provideFailure(): array
    {
        return [
            [
                'NonExistentClass',
                'Expected string to be interface name. Got "NonExistentClass".',
            ],
            [
                StubClass::class,
                'Expected string to be interface name. Got "Vivarium\Test\Assertion\Stub\StubClass".',
            ],
        ];
    }

    /** @return array<array{0:int, 1:string}> */
    public static function provideInvalid(): array
    {
        return [
            [42, 'Expected value to be string. Got int.'],
        ];
    }
}
