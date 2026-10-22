<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Traversable;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Object\IsInstanceOf;
use Vivarium\Test\Assertion\Stub\Stub;
use Vivarium\Test\Assertion\Stub\StubClass;
use Vivarium\Test\Assertion\Stub\StubClassExtension;

#[CoversClass(IsInstanceOf::class)]
final class IsInstanceOfTest extends TestCase
{
    /** @param class-string $class */
    #[DataProvider('provideSuccess')]
    public function testAssert(object $target, string $class): void
    {
        static::expectNotToPerformAssertions();

        (new IsInstanceOf($class))
            ->assert($target);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(object|string $target, string $class, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @phpstan-ignore argument.type
         */
        (new IsInstanceOf($class))
            ->assert($target);
    }

        /** @param class-string $class */
    #[DataProvider('provideSuccess')]
    public function testInvoke(object $target, string $class): void
    {
        static::assertTrue(
            (new IsInstanceOf($class))($target),
        );
    }

    /** @param class-string $class */
    #[DataProvider('provideFailure')]
    public function testInvokeFailure(object $target, string $class): void
    {
        static::assertFalse(
            (new IsInstanceOf($class))($target),
        );
    }

    /** @return array<array{0:object, 1:class-string}> */
    public static function provideSuccess(): array
    {
        return [
            [new StubClass(), Stub::class],
            [new StubClassExtension(), Stub::class],
            [new StubClassExtension(), StubClass::class],
            [new StubClassExtension(), StubClassExtension::class],
        ];
    }

    /** @return array<array{0:object, 1:class-string, 2:string}> */
    public static function provideFailure(): array
    {
        return [
            [
                new stdClass(),
                Traversable::class,
                'Expected object to be instance of "Traversable". Got "stdClass".',
            ],
        ];
    }

    /** @return array<array{0:string|object, 1:string, 2:string}> */
    public static function provideInvalid(): array
    {
        return [
            [
                'RandomString',
                Traversable::class,
                'Expected value to be object. Got string.',
            ],
            [
                new stdClass(),
                'RandomString',
                'Argument must be a class or interface name. Got "RandomString"',
            ],
        ];
    }
}
