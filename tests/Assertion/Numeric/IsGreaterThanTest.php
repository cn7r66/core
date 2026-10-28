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
use Vivarium\Assertion\Numeric\IsGreaterThan;

use function preg_quote;

use const INF;
use const NAN;

#[CoversClass(IsGreaterThan::class)]
final class IsGreaterThanTest extends TestCase
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

    #[TestWith([0, 'Custom int 0.'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsGreaterThan(5))->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new IsGreaterThan(5);

        return [
            'accepts 6' => [$assertion, 6, null],
            'accepts 5.5' => [$assertion, 5.5, null],
            'accepts INF' => [$assertion, INF, null],
            'rejects 5' => [$assertion, 5, 'Expected value to be greater than 5. Got int 5.'],
            'rejects 4.9' => [$assertion, 4.9, 'Expected value to be greater than 5. Got float 4.9.'],
            'rejects NAN' => [$assertion, NAN, 'Expected value to be greater than 5. Got float NAN.'],
            'numeric string' => [$assertion, '6', 'Expected value to be int or float. Got string "6".'],
            'null' => [$assertion, null, 'Expected value to be int or float. Got null.'],
        ];
    }
}
