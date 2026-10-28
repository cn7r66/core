<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Numeric;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Numeric\IsInClosedRange;

use function preg_quote;

use const NAN;

#[CoversClass(IsInClosedRange::class)]
final class IsInClosedRangeTest extends TestCase
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

    #[TestWith([11, 'Custom int 11.'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsInClosedRange(0, 10))->assert($value, 'Custom {value}.');
    }

    #[TestWith([3, 1, 'Expected lower bound not to exceed upper bound 1. Got int 3.'])]
    public function testConstructorRejectsInvertedBounds(int|float $min, int|float $max, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        new IsInClosedRange($min, $max);
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new IsInClosedRange(0, 10);

        return [
            'accepts 0' => [$assertion, 0, null],
            'accepts 5' => [$assertion, 5, null],
            'accepts 10' => [$assertion, 10, null],
            'accepts 2.5' => [$assertion, 2.5, null],
            'rejects -1' => [$assertion, -1, 'Expected value to be in [0, 10]. Got int -1.'],
            'rejects 11' => [$assertion, 11, 'Expected value to be in [0, 10]. Got int 11.'],
            'rejects 10.1' => [$assertion, 10.1, 'Expected value to be in [0, 10]. Got float 10.1.'],
            'not a number' => [$assertion, NAN, 'Expected value to be in [0, 10]. Got float NAN.'],
            'numeric string' => [$assertion, '5', 'Expected value to be int or float. Got string "5".'],
        ];
    }
}
