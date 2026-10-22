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
use Vivarium\Assertion\Type\IsAssignableToClass;
use Vivarium\Test\Assertion\Stub\Stub;
use Vivarium\Test\Assertion\Stub\StubClass;
use Vivarium\Test\Assertion\Stub\StubClassExtension;

use function sprintf;

#[CoversClass(IsAssignableToClass::class)]
final class IsAssignableToClassTest extends TestCase
{
    /** @param class-string $type */
    #[DataProvider('provideSuccess')]
    public function testAssert(string $class, string $type): void
    {
        static::expectNotToPerformAssertions();

        (new IsAssignableToClass($type))
            ->assert($class);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(string $class, string $type, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @phpstan-ignore argument.type
         */
        (new IsAssignableToClass($type))
            ->assert($class);
    }

    /** @param class-string $type */
    #[DataProvider('provideSuccess')]
    public function testInvoke(string $class, string $type): void
    {
        static::assertTrue(
            (new IsAssignableToClass($type))($class),
        );
    }

    /** @param class-string $type */
    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string $class, string $type): void
    {
        static::assertFalse(
            (new IsAssignableToClass($type))($class),
        );
    }

    /** @return array<array<class-string>> */
    public static function provideSuccess(): array
    {
        return [
            [Stub::class, Stub::class],
            [StubClass::class, Stub::class],
            [StubClassExtension::class, StubClass::class],
        ];
    }

    /** @return array<array{0:class-string, 1:class-string, 2:string}> */
    public static function provideFailure(): array
    {
        return [
            [
                StubClass::class,
                StubClassExtension::class,
                sprintf(
                    'Expected class "%s" to be assignable to "%2$s".',
                    StubClass::class,
                    StubClassExtension::class,
                ),
            ],
        ];
    }

    /** @return array<array{0:string, 1:string, 2:string}> */
    public static function provideInvalid(): array
    {
        return [
            [
                StubClass::class,
                'RandomString',
                'Expected string to be class or interface name. Got "RandomString"',
            ],
            [
                'RandomString',
                StubClass::class,
                'Expected string to be class or interface name. Got "RandomString"',
            ],
        ];
    }
}
