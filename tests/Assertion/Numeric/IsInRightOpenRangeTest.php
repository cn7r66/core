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
use Vivarium\Assertion\Numeric\IsInRightOpenRange;

use function preg_quote;

use const NAN;

#[CoversClass(IsInRightOpenRange::class)]
final class IsInRightOpenRangeTest extends TestCase
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

        (new IsInRightOpenRange(0, 10))->assert($value, 'Custom {value}.');
    }

    #[TestWith([3, 1, 'Expected lower bound not to exceed upper bound 1. Got int 3.'])]
    public function testConstructorRejectsInvertedBounds(int|float $min, int|float $max, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        new IsInRightOpenRange($min, $max);
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new IsInRightOpenRange(0, 10);

        return [
            'accepts 0' => [$assertion, 0, null],
            'accepts 9' => [$assertion, 9, null],
            'accepts 9.9' => [$assertion, 9.9, null],
            'rejects 10' => [$assertion, 10, 'Expected value to be in [0, 10). Got int 10.'],
            'rejects -0.1' => [$assertion, -0.1, 'Expected value to be in [0, 10). Got float -0.1.'],
            'not a number' => [$assertion, NAN, 'Expected value to be in [0, 10). Got float NAN.'],
            'numeric string' => [$assertion, '5', 'Expected value to be int or float. Got string "5".'],
            'empty range' => [new IsInRightOpenRange(0, 0), 0, 'Expected value to be in [0, 0). Got int 0.'],
        ];
    }
}
