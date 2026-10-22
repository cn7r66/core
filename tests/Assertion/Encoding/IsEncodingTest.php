<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Encoding;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Encoding\IsEncoding;
use Vivarium\Assertion\Exception\AssertionFailed;

#[CoversClass(IsEncoding::class)]
final class IsEncodingTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string $encoding): void
    {
        static::expectNotToPerformAssertions();

        (new IsEncoding())->assert($encoding);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(string|int $encoding, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsEncoding())->assert($encoding);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string $encoding): void
    {
        static::assertTrue(
            (new IsEncoding())($encoding),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string|int $encoding): void
    {
        static::assertFalse(
            (new IsEncoding())($encoding),
        );
    }

    /** @return array<array<string>> */
    public static function provideSuccess(): array
    {
        return [
            ['UTF-8'],
            ['UTF-32'],
            ['ASCII'],
        ];
    }

    /** @return array<array<string>> */
    public static function provideFailure(): array
    {
        return [
            ['Windows-1251', '"Windows-1251" is not a valid encoding.'],
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
