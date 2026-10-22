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
use Vivarium\Assertion\Type\IsSubclassOf;
use Vivarium\Test\Assertion\Stub\Stub;
use Vivarium\Test\Assertion\Stub\StubClass;
use Vivarium\Test\Assertion\Stub\StubClassExtension;

use function sprintf;

#[CoversClass(IsSubclassOf::class)]
final class IsSubclassOfTest extends TestCase
{
    /** @param class-string $subclass */
    #[DataProvider('provideSuccess')]
    public function testAssert(string $class, string $subclass): void
    {
        static::expectNotToPerformAssertions();

        (new IsSubclassOf($subclass))
            ->assert($class);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(string $class, string $subclass, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @phpstan-ignore argument.type
         */
        (new IsSubclassOf($subclass))
            ->assert($class);
    }

    /** @param class-string $subclass */
    #[DataProvider('provideSuccess')]
    public function testInvoke(string $class, string $subclass): void
    {
        static::assertTrue(
            (new IsSubclassOf($subclass))($class),
        );
    }

    /** @param class-string $subclass */
    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string $class, string $subclass): void
    {
        static::assertFalse(
            (new IsSubclassOf($subclass))($class),
        );
    }

    /** @return array<array<class-string>> */
    public static function provideSuccess(): array
    {
        return [
            [
                StubClass::class,
                Stub::class,
            ],
            [
                StubClassExtension::class,
                StubClass::class,
            ],
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
                    'Expected class "%s" to be subclass of "%2$s".',
                    StubClass::class,
                    StubClassExtension::class,
                ),
            ],
        ];
    }

    /** @return array<array<string>> */
    public static function provideInvalid(): array
    {
        return [
            [
                'RandomString',
                StubClass::class,
                'Expected string to be class or interface name. Got "RandomString"',
            ],
            [
                StubClass::class,
                'RandomString',
                'Expected string to be class or interface name. Got "RandomString"',
            ],
        ];
    }
}
