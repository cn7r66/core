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
use Vivarium\Assertion\Type\IsAssignableToProperty;
use Vivarium\Test\Assertion\Stub\Stub;
use Vivarium\Test\Assertion\Stub\StubClass;
use Vivarium\Test\Assertion\Stub\StubClassExtension;

use function sprintf;

#[CoversClass(IsAssignableToProperty::class)]
final class IsAssignableToPropertyTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string $type, string $class, string $property): void
    {
        static::expectNotToPerformAssertions();

        (new IsAssignableToProperty($class, $property))
            ->assert($type);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(string $type, string $class, string $property, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsAssignableToProperty($class, $property))
            ->assert($type);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string $type, string $class, string $property): void
    {
        static::assertTrue(
            (new IsAssignableToProperty($class, $property))($type),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string $type, string $class, string $property): void
    {
        static::assertFalse(
            (new IsAssignableToProperty($class, $property))($type),
        );
    }

    /** @return array<array{0:string, 1:string, 2:string}> */
    public static function provideSuccess(): array
    {
        return [
            ['int', StubClass::class, 'prop'],
            [StubClass::class, StubClass::class, 'stubProp'],
            [StubClassExtension::class, StubClass::class, 'stubProp'],
            [StubClass::class, StubClass::class, 'classProp'],
            [StubClassExtension::class, StubClass::class, 'classProp'],
        ];
    }

    /** @return array<array{0:string, 1:string, 2:string, 3:string}> */
    public static function provideFailure(): array
    {
        return [
            [
                'string',
                StubClass::class,
                'prop',
                sprintf('Expected type "string" to be assignable to property prop of class "%s".', StubClass::class),
            ],
            [
                Stub::class,
                StubClass::class,
                'classProp',
                sprintf(
                    'Expected type "%s" to be assignable to property classProp of class "%2$s".',
                    Stub::class,
                    StubClass::class,
                ),
            ],
        ];
    }

    /** @return array<array{0:string, 1:string, 2:string, 3:string}> */
    public static function provideInvalid(): array
    {
        return [
            [
                'int',
                'NonExistentClass',
                'prop',
                'Expected string to be class name. Got "NonExistentClass".',
            ],
            [
                'int',
                StubClass::class,
                'nonExistentProp',
                'Expected "' . StubClass::class . '" to have a property named "nonExistentProp".',
            ],
        ];
    }
}
