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
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Comparison\IsOneOf;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Test\Equality\Stub\EqualityStub;

use function preg_quote;

#[CoversClass(IsOneOf::class)]
final class IsOneOfTest extends TestCase
{
    /** @param array<mixed> $choices */
    #[DataProvider('provideValues')]
    public function testCheck(array $choices, mixed $value, string|null $message): void
    {
        self::assertSame($message === null, (new IsOneOf($choices))->check($value));
    }

    /** @param array<mixed> $choices */
    #[DataProvider('provideValues')]
    public function testAssert(array $choices, mixed $value, string|null $message): void
    {
        if ($message === null) {
            $this->expectNotToPerformAssertions();
        }

        if ($message !== null) {
            $this->expectException(AssertionFailed::class);
            $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');
        }

        (new IsOneOf($choices))->assert($value);
    }

    /** @param array<mixed> $choices */
    #[TestWith([[1, 2], 3, 'Custom int 3 against [1, 2].'])]
    public function testAssertWithCustomMessage(array $choices, mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsOneOf($choices))->assert($value, 'Custom {value} against {choices}.');
    }

    public function testConstructorRejectsEmptyChoices(): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote('Expected choices not to be empty.', '/') . '$/');

        new IsOneOf([]);
    }

    /** @return array<string, array{array<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'hit' => [[1, 5, 7], 5, null],
            'equality object' => [[new EqualityStub()], new EqualityStub(), null],
            'miss' => [[1, 5, 7], 27, 'Expected value to be one of [1, 5, 7]. Got int 27.'],
            'numeric string' => [[5], '5', 'Expected value to be one of [5]. Got string "5".'],
            'capped choices' => [
                [1, 2, 3, 4, 5, 6],
                7,
                'Expected value to be one of [1, 2, 3, 4, 5, … (6 total)]. Got int 7.',
            ],
        ];
    }
}
