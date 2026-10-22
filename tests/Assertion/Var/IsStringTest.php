<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Var;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Var\IsString;

#[CoversClass(IsString::class)]
final class IsStringTest extends TestCase
{
        #[DataProvider('provideSuccess')]
    public function testAssert(mixed $var): void
    {
        static::expectNotToPerformAssertions();

        (new IsString())
            ->assert($var);
    }

    #[DataProvider('provideFailure')]
    public function testAssertException(mixed $var, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsString())
            ->assert($var);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(mixed $var): void
    {
        static::assertTrue(
            (new IsString())($var),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(mixed $var): void
    {
        static::assertFalse(
            (new IsString())($var),
        );
    }

    /** @return array<array<string>> */
    public static function provideSuccess(): array
    {
        return [
            [''],
            ['Hello World'],
            ['a'],
            ['42'],
        ];
    }

    /** @return array<array{0:mixed, 1:string}> */
    public static function provideFailure(): array
    {
        return [
            [[], 'Expected value to be string. Got array.'],
            [42, 'Expected value to be string. Got int.'],
        ];
    }
}
