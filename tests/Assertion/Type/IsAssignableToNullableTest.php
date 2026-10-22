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
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Type\IsAssignableToNullable;
use Vivarium\Test\Assertion\Stub\StubClass;

#[CoversClass(IsAssignableToNullable::class)]
final class IsAssignableToNullableTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string $type, string $nullable): void
    {
        static::expectNotToPerformAssertions();

        (new IsAssignableToNullable($nullable))
            ->assert($type);
    }

    #[DataProvider('provideFailure')]
    public function testAssertException(string $type, string $nullable, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsAssignableToNullable($nullable))
            ->assert($type);
    }

    public function testConstructorException(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('Expected string to be a nullable type (starting with ?). Got "string".');

        new IsAssignableToNullable('string');
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string $type, string $nullable): void
    {
        static::assertTrue(
            (new IsAssignableToNullable($nullable))($type),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string $type, string $nullable): void
    {
        static::assertFalse(
            (new IsAssignableToNullable($nullable))($type),
        );
    }

    /** @return array<array<string>> */
    public static function provideSuccess(): array
    {
        return [
            ['string', '?string'],
            ['?string', '?string'],
            ['int', '?int'],
            ['?int', '?int'],
            ['float', '?float'],
            ['int', '?float'],
            [stdClass::class, '?' . stdClass::class],
            [StubClass::class, '?' . StubClass::class],
        ];
    }

    /** @return array<array<string>> */
    public static function provideFailure(): array
    {
        return [
            [
                'int',
                '?string',
                'Expected type to be assignable to nullable type "?string". Got "int".',
            ],
            [
                '?int',
                '?string',
                'Expected type to be assignable to nullable type "?string". Got "?int".',
            ],
            [
                'string',
                '?int',
                'Expected type to be assignable to nullable type "?int". Got "string".',
            ],
            [
                stdClass::class,
                '?string',
                'Expected type to be assignable to nullable type "?string". Got "stdClass".',
            ],
        ];
    }
}
