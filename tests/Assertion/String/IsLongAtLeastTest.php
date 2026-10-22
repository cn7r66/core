<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\String;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\String\IsLongAtLeast;

#[CoversClass(IsLongAtLeast::class)]
final class IsLongAtLeastTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string $string, int $length, string $encoding): void
    {
        static::expectNotToPerformAssertions();

        (new IsLongAtLeast($length, $encoding))
            ->assert($string);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideNonValid')]
    public function testAssertException(string|int $string, int $length, string $encoding, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsLongAtLeast($length, $encoding))
            ->assert($string);
    }

        #[DataProvider('provideSuccess')]
    public function testInvoke(string $string, int $length, string $encoding): void
    {
        static::assertTrue((new IsLongAtLeast($length, $encoding))($string));
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string $string, int $length, string $encoding): void
    {
        static::assertFalse((new IsLongAtLeast($length, $encoding))($string));
    }

    /** @return array<array{0:string, 1:int, 2:string}> */
    public static function provideSuccess(): array
    {
        return [
            ['Hello', 3, 'UTF-8'],
            ['Hello', 5, 'UTF-8'],
            ['π', 1, 'UTF-8'],
        ];
    }

    /** @return array<array{0:string, 1:int, 2:string, 3:string}> */
    public static function provideFailure(): array
    {
        return [
            ['Hello', 6, 'UTF-8', 'Expected string to be long at least 6. Got 5'],
            ['π', 2, 'UTF-8', 'Expected string to be long at least 2. Got 1.'],
        ];
    }

    /** @return array<array{0:int|string, 1:int, 2:string, 3:string}> */
    public static function provideNonValid(): array
    {
        return [
            ['Hello', 3, 'Foo', '"Foo" is not a valid system encoding.'],
            ['Hello', 0, 'UTF-8', 'Expected number to be greater than 0. Got 0.'],
            [42, 5, 'UTF-8', 'Expected value to be string. Got int.'],
        ];
    }
}
