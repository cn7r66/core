<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Composite;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\Composite\Each;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Var\IsInteger;

use function preg_quote;

#[CoversClass(Each::class)]
final class EachTest extends TestCase
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

    public function testAssertWithCustomMessage(): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote('Custom string "a".', '/') . '$/');

        (new Each(
            new IsInteger(),
        ))->assert([1, 'a'], 'Custom {value}.');
    }

    public function testAssertPassesMessage(): void
    {
        $child = $this->createMock(Assertion::class);
        $child->expects(self::once())->method('assert')->with('a', 'Custom');

        (new Each($child))->assert(['a'], 'Custom');
    }

    #[TestWith(['abc', 'Expected value to be array. Got string "abc".'])]
    public function testAssertKeepsPreconditionMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new Each(
            new IsInteger(),
        ))->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $eachInteger = new Each(
            new IsInteger(),
        );

        return [
            'ints' => [$eachInteger, [1, 2], null],
            'empty array' => [$eachInteger, [], null],
            'map of ints' => [$eachInteger, ['a' => 1], null],
            'one string' => [$eachInteger, [1, 'a', 'b'], 'Expected value to be int. Got string "a".'],
            'later string' => [$eachInteger, [1, 2, 'a', 4], 'Expected value to be int. Got string "a".'],
            'not an array' => [$eachInteger, 'abc', 'Expected value to be array. Got string "abc".'],
        ];
    }
}
