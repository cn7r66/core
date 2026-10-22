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
use stdClass;
use Traversable;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Type\ImplementsInterface;
use Vivarium\Test\Assertion\Stub\Stub;
use Vivarium\Test\Assertion\Stub\StubClass;

#[CoversClass(ImplementsInterface::class)]
final class ImplementsInterfaceTest extends TestCase
{
    /** @param class-string $interface */
    #[DataProvider('provideSuccess')]
    public function testAssert(string $class, string $interface): void
    {
        static::expectNotToPerformAssertions();

        (new ImplementsInterface($interface))
            ->assert($class);
    }

    /** @param class-string $interface */
    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(string $class, string $interface, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new ImplementsInterface($interface))
            ->assert($class);
    }

    /** @param class-string $interface */
    #[DataProvider('provideSuccess')]
    public function testInvoke(string $class, string $interface): void
    {
        static::assertTrue(
            (new ImplementsInterface($interface))($class),
        );
    }

    /** @param class-string $interface */
    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string $class, string $interface): void
    {
        static::assertFalse(
            (new ImplementsInterface($interface))($class),
        );
    }

    /** @return array<array<class-string>> */
    public static function provideSuccess(): array
    {
        return [
            [StubClass::class, Stub::class],
        ];
    }

    /** @return array<array{0:class-string, 1:class-string, 2:string}> */
    public static function provideFailure(): array
    {
        return [
            [
                stdClass::class,
                Traversable::class,
                'Expected class "stdClass" to implements "Traversable".',
            ],
        ];
    }

    /** @return array<array{0:class-string, 1:class-string, 2:string}> */
    public static function provideInvalid(): array
    {
        return [
            [
                stdClass::class,
                stdClass::class,
                'Expected string to be interface name. Got "stdClass".',
            ],
            [
                Traversable::class,
                Traversable::class,
                'Expected string to be class name. Got "Traversable".',
            ],
        ];
    }
}
