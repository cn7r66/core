<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Comparison;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Comparison\IsEqualsTo;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Test\Equality\Stub\EqualityStub;

#[CoversClass(IsEqualsTo::class)]
final class IsEqualsToTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(mixed $first, mixed $second): void
    {
        static::expectNotToPerformAssertions();

        (new IsEqualsTo($first))
            ->assert($second);
    }

    #[DataProvider('provideFailure')]
    public function testAssertException(mixed $first, mixed $second, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsEqualsTo($first))
            ->assert($second);
    }

    /** @return array<array<scalar|object>> */
    public static function provideSuccess(): array
    {
        $stdClass = new stdClass();

        return [
            ['Foo', 'Foo'],
            [5, 5],
            [-1, -1],
            [$stdClass, $stdClass],
            [new EqualityStub(), new EqualityStub()],
        ];
    }

    /** @return array<array{0: mixed, 1:mixed, 2:string}> */
    public static function provideFailure(): array
    {
        return [
            [
                'RandomString',
                'Hello World',
                'Expected value to be equals to "RandomString". Got "Hello World".',
            ],
            [
                new EqualityStub(),
                5,
                'Expected value to be equals to "Vivarium\Test\Equality\Stub\EqualityStub". Got 5.',
            ],
        ];
    }
}
