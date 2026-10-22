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
use Vivarium\Assertion\Type\IsClass;
use Vivarium\Test\Assertion\Stub\Stub;
use Vivarium\Test\Assertion\Stub\StubClass;

#[CoversClass(IsClass::class)]
final class IsClassTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string $type): void
    {
        static::expectNotToPerformAssertions();

        (new IsClass())
            ->assert($type);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(string|int $type, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsClass())
            ->assert($type);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string $type): void
    {
        static::assertTrue(
            (new IsClass())($type),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string $type): void
    {
        static::assertFalse(
            (new IsClass())($type),
        );
    }

    /** @return array<array<string>> */
    public static function provideSuccess(): array
    {
        return [
            ['stdClass'],
            [StubClass::class],
        ];
    }

    /** @return array<array<string>> */
    public static function provideFailure(): array
    {
        return [
            [
                'NonExistentClass',
                'Expected string to be class name. Got "NonExistentClass".',
            ],
            [
                Stub::class,
                'Expected string to be class name. Got "Vivarium\Test\Assertion\Stub\Stub".',
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
