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
use Vivarium\Assertion\String\StartsWith;

#[CoversClass(StartsWith::class)]
final class StartsWithTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string $string, string $start): void
    {
        static::expectNotToPerformAssertions();

        (new StartsWith($start))
            ->assert($string);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideNonString')]
    public function testAssertException(string|int $string, string $start, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new StartsWith($start))
            ->assert($string);
    }

        #[DataProvider('provideSuccess')]
    public function testInvoke(string $string, string $start): void
    {
        static::assertTrue((new StartsWith($start))($string));
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string $string, string $start, string $message): void
    {
        static::assertFalse((new StartsWith($start))($string));
    }

    /** @return array<array<string>> */
    public static function provideSuccess(): array
    {
        return [
            ['Hello World', 'Hello'],
            ['Hello World', 'H'],
            ['Hello World', 'Hello W'],
            ['Hello World', 'Hello '],
        ];
    }

    /** @return array<array<string>> */
    public static function provideFailure(): array
    {
        return [
            ['Hello World', 'World', 'Expected that string "Hello World" starts with "World".'],
        ];
    }

    /** @return array<array{0:int, 1:string, 2:string}> */
    public static function provideNonString(): array
    {
        return [
            [42, 'H', 'Expected value to be string. Got int.'],
        ];
    }
}
