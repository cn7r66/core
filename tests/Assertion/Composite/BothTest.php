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
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\Comparison\IsSameAs;
use Vivarium\Assertion\Composite\Both;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Failure;
use Vivarium\Assertion\Var\IsInteger;

use function preg_quote;

#[CoversClass(Both::class)]
final class BothTest extends TestCase
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
        $this->expectExceptionMessageMatches('/^' . preg_quote('Custom int 6.', '/') . '$/');

        (new Both(
            new IsInteger(),
            new IsSameAs(5),
        ))->assert(6, 'Custom {value}.');
    }

    public function testAssertStopsAtFirstFailure(): void
    {
        $first = self::createStub(Assertion::class);
        $first->method('assert')->willThrowException(new AssertionFailed(new Failure('First {value}.', 1)));
        $second = $this->createMock(Assertion::class);
        $second->expects(self::never())->method('assert');

        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^First int 1\.$/');

        (new Both($first, $second))->assert(1);
    }

    public function testAssertPassesMessage(): void
    {
        $child = $this->createMock(Assertion::class);
        $child->expects(self::exactly(2))->method('assert')->with('a', 'Custom');

        (new Both($child, $child))->assert('a', 'Custom');
    }

    public function testCheckNeverAsserts(): void
    {
        $child = $this->createMock(Assertion::class);
        $child->expects(self::never())->method('assert');
        $child->method('check')->willReturn(true);

        (new Both($child, $child))->check(1);
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $isFive = new Both(
            new IsInteger(),
            new IsSameAs(5),
        );

        return [
            'int five' => [$isFive, 5, null],
            'int six' => [$isFive, 6, 'Expected value to be the same as 5. Got int 6.'],
            'string' => [$isFive, 'a', 'Expected value to be int. Got string "a".'],
        ];
    }
}
