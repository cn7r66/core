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
use Vivarium\Assertion\String\HasLengthAtMost;

use function preg_quote;

#[CoversClass(HasLengthAtMost::class)]
final class HasLengthAtMostTest extends TestCase
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

    #[TestWith(['abcdefgh', 'Custom string "abcdefgh".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new HasLengthAtMost(3))->assert($value, 'Custom {value}.');
    }

    #[TestWith([-1, 'Expected length to be greater than or equal to 0. Got int -1.'])]
    public function testConstructorRejectsNegativeLength(int $length, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        new HasLengthAtMost($length);
    }

    #[TestWith(['nope', 'Expected value to be an encoding known to mbstring. Got string "nope".'])]
    public function testConstructorRejectsUnknownEncoding(string $encoding, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        new HasLengthAtMost(1, $encoding);
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new HasLengthAtMost(3);

        return [
            'accepts abc' => [$assertion, 'abc', null],
            'accepts empty' => [$assertion, '', null],
            'accepts àèì' => [$assertion, 'àèì', null],
            'rejects abcd' => [$assertion, 'abcd', 'Expected value to have a length of at most 3. Got string "abcd".'],
            'combining mark counts as two' => [new HasLengthAtMost(2), "e\u{0301}", null],
            'latin1 byte' => [new HasLengthAtMost(1, 'ISO-8859-1'), "\xE9", null],
            'int' => [$assertion, 1, 'Expected value to be string. Got int 1.'],
        ];
    }
}
