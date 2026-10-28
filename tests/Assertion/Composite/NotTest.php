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
use Vivarium\Assertion\Composite\Not;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Var\IsInteger;
use Vivarium\Assertion\Var\IsNull;

use function preg_quote;

#[CoversClass(Not::class)]
final class NotTest extends TestCase
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
        $this->expectExceptionMessageMatches('/^' . preg_quote('Custom null.', '/') . '$/');

        (new Not(new IsNull()))->assert(null, 'Custom {value}.');
    }

    public function testCheckNeverAsserts(): void
    {
        $child = $this->createMock(Assertion::class);
        $child->expects(self::never())->method('assert');
        $child->method('check')->willReturn(true);

        (new Not($child))->check(1);
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'not null, given int' => [new Not(new IsNull()), 1, null],
            'not int, given string' => [new Not(new IsInteger()), 'a', null],
            'not null, given null' => [
                new Not(new IsNull()),
                null,
                'Expected value not to satisfy ' . IsNull::class . '. Got null.',
            ],
            'not int, given int' => [
                new Not(new IsInteger()),
                5,
                'Expected value not to satisfy ' . IsInteger::class . '. Got int 5.',
            ],
        ];
    }
}
