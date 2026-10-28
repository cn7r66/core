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
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Var\IsNumber;

use function preg_quote;

use const INF;
use const NAN;

#[CoversClass(IsNumber::class)]
final class IsNumberTest extends TestCase
{
    #[DataProvider('provideValues')]
    public function testCheck(mixed $value, string|null $message): void
    {
        self::assertSame($message === null, (new IsNumber())->check($value));
    }

    #[DataProvider('provideValues')]
    public function testAssert(mixed $value, string|null $message): void
    {
        if ($message === null) {
            $this->expectNotToPerformAssertions();
        }

        if ($message !== null) {
            $this->expectException(AssertionFailed::class);
            $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');
        }

        (new IsNumber())->assert($value);
    }

    #[TestWith(['12', 'Custom string "12".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsNumber())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'int' => [1, null],
            'negative int' => [-1, null],
            'float' => [1.5, null],
            'not a number' => [NAN, null],
            'infinity' => [INF, null],
            'numeric string' => ['12', 'Expected value to be int or float. Got string "12".'],
            'float string' => ['1.5', 'Expected value to be int or float. Got string "1.5".'],
            'bool' => [true, 'Expected value to be int or float. Got bool true.'],
            'null' => [null, 'Expected value to be int or float. Got null.'],
        ];
    }
}
