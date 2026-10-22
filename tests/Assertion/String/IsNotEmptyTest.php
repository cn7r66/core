<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Assertion\String;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\String\IsNotEmpty;

#[CoversClass(IsNotEmpty::class)]
final class IsNotEmptyTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string $string): void
    {
        static::expectNotToPerformAssertions();

        (new IsNotEmpty())
            ->assert($string);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideNonValid')]
    public function testAssertException(string|int $string, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsNotEmpty())
            ->assert($string);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string $string): void
    {
        static::assertTrue((new IsNotEmpty())($string));
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string $string): void
    {
        static::assertFalse((new IsNotEmpty())($string));
    }

    /** @return array<array<string>> */
    public static function provideSuccess(): array
    {
        return [
            ['Foo'],
            ['A'],
            ['Hello World'],
        ];
    }

    /** @return array<array<string>> */
    public static function provideFailure(): array
    {
        return [
            ['', 'Expected string to be not empty.'],
            [' ', 'Expected string to be not empty.'],
            ['       ', 'Expected string to be not empty.'],
        ];
    }

    /** @return array<array{0:int, 1:string}> */
    public static function provideNonValid(): array
    {
        return [
            [42, 'Expected value to be string. Got int.'],
        ];
    }
}
