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
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\String\HasLengthBetween;

use function preg_quote;

#[CoversClass(HasLengthBetween::class)]
final class HasLengthBetweenTest extends TestCase
{
    /** @param Assertion<mixed> $assertion */
    #[DataProvider('provideValues')]
    public function testCheck(Assertion $assertion, mixed $value, string|null $message): void
    {
        self::assertSame($message === null, $assertion->check($value));
    }

    /** @param Assertion<mixed> $assertion */
    #[DataProvider('provideValues')]
    public function testAssert(Assertion $assertion, mixed $value, string|null $message): void
    {
        if ($message === null) {
            $this->expectNotToPerformAssertions();
        }

        if ($message !== null) {
            $this->expectException(AssertionFailed::class);
            $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');
        }

        $assertion->assert($value);
    }

    #[TestWith(['abcd', 'Custom string "abcd".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new HasLengthBetween(1, 3))->assert($value, 'Custom {value}.');
    }

    #[TestWith([-1, 2, 'Expected minimum length to be greater than or equal to 0. Got int -1.'])]
    public function testConstructorRejectsNegativeMinimum(int $min, int $max, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        new HasLengthBetween($min, $max);
    }

    #[TestWith([3, 1, 'Expected minimum length not to exceed maximum length 1. Got int 3.'])]
    public function testConstructorRejectsInvertedBounds(int $min, int $max, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        new HasLengthBetween($min, $max);
    }

    #[TestWith(['nope', 'Expected value to be an encoding known to mbstring. Got string "nope".'])]
    public function testConstructorRejectsUnknownEncoding(string $encoding, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        new HasLengthBetween(1, 2, $encoding);
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new HasLengthBetween(1, 3);

        return [
            'lower bound' => [$assertion, 'a', null],
            'upper bound' => [$assertion, 'àèì', null],
            'too short' => [$assertion, '', 'Expected value to have a length between 1 and 3. Got string "".'],
            'too long' => [$assertion, 'abcd', 'Expected value to have a length between 1 and 3. Got string "abcd".'],
            'latin1 bytes' => [new HasLengthBetween(1, 2, 'ISO-8859-1'), "\xE9\xE8", null],
            'int' => [$assertion, 1, 'Expected value to be string. Got int 1.'],
        ];
    }
}
