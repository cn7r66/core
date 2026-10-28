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
use Vivarium\Assertion\Numeric\IsLessThanOrEqualTo;

use function preg_quote;

use const NAN;

#[CoversClass(IsLessThanOrEqualTo::class)]
final class IsLessThanOrEqualToTest extends TestCase
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

    #[TestWith([9, 'Custom int 9.'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsLessThanOrEqualTo(5))->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new IsLessThanOrEqualTo(5);

        return [
            'accepts 5' => [$assertion, 5, null],
            'accepts 5.0' => [$assertion, 5.0, null],
            'accepts -1' => [$assertion, -1, null],
            'rejects 6' => [$assertion, 6, 'Expected value to be less than or equal to 5. Got int 6.'],
            'rejects NAN' => [$assertion, NAN, 'Expected value to be less than or equal to 5. Got float NAN.'],
            'numeric string' => [$assertion, '6', 'Expected value to be int or float. Got string "6".'],
            'null' => [$assertion, null, 'Expected value to be int or float. Got null.'],
        ];
    }
}
