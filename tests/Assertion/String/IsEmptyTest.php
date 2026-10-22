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
use Vivarium\Assertion\String\IsEmpty;

#[CoversClass(IsEmpty::class)]
final class IsEmptyTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string $string): void
    {
        static::expectNotToPerformAssertions();

        (new IsEmpty())
            ->assert($string);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideNonString')]
    public function testAssertException(string|int $string, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsEmpty())
            ->assert($string);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string $string): void
    {
        static::assertTrue((new IsEmpty())($string));
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string $string): void
    {
        static::assertFalse((new IsEmpty())($string));
    }

    /** @return array<array<string>> */
    public static function provideSuccess(): array
    {
        return [
            [''],
            ['        '],
        ];
    }

    /** @return array<array<string>> */
    public static function provideFailure(): array
    {
        return [
            ['Hello World', 'Expected string to be empty. Got "Hello World".'],
        ];
    }

    /** @return array<array{0:int, 1:string}> */
    public static function provideNonString(): array
    {
        return [
            [42, 'Expected value to be string. Got int.'],
        ];
    }
}
