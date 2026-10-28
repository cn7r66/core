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
use Vivarium\Assertion\Composite\NullOr;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Var\IsInteger;

use function preg_quote;

#[CoversClass(NullOr::class)]
final class NullOrTest extends TestCase
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

        (new NullOr(
            new IsInteger(),
        ))->assert('a', 'Custom {value}.');
    }

    public function testAssertPassesMessage(): void
    {
        $child = $this->createMock(Assertion::class);
        $child->expects(self::once())->method('assert')->with('a', 'Custom');

        (new NullOr($child))->assert('a', 'Custom');
    }

    public function testAssertSkipsChildOnNull(): void
    {
        $child = $this->createMock(Assertion::class);
        $child->expects(self::never())->method('assert');
        $child->expects(self::never())->method('check');

        (new NullOr($child))->assert(null);
        self::assertTrue((new NullOr($child))->check(null));
    }

    public function testCheckNeverAsserts(): void
    {
        $child = $this->createMock(Assertion::class);
        $child->expects(self::never())->method('assert');
        $child->method('check')->willReturn(true);

        (new NullOr($child))->check(1);
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $nullOrInteger = new NullOr(
            new IsInteger(),
        );

        return [
            'null' => [$nullOrInteger, null, null],
            'int' => [$nullOrInteger, 5, null],
            'string' => [$nullOrInteger, 'a', 'Expected value to be int. Got string "a".'],
            'false' => [$nullOrInteger, false, 'Expected value to be int. Got bool false.'],
        ];
    }
}
