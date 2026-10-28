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
use Vivarium\Assertion\Var\IsInteger;

use function preg_quote;

use const PHP_INT_MAX;

#[CoversClass(IsInteger::class)]
final class IsIntegerTest extends TestCase
{
    #[DataProvider('provideValues')]
    public function testCheck(mixed $value, string|null $message): void
    {
        self::assertSame($message === null, (new IsInteger())->check($value));
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

        (new IsInteger())->assert($value);
    }

    #[TestWith(['1', 'Custom string "1".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsInteger())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'zero' => [0, null],
            'positive' => [1, null],
            'negative' => [-1, null],
            'max' => [PHP_INT_MAX, null],
            'numeric string' => ['1', 'Expected value to be int. Got string "1".'],
            'integral float' => [1.0, 'Expected value to be int. Got float 1.0.'],
            'bool' => [true, 'Expected value to be int. Got bool true.'],
        ];
    }
}
