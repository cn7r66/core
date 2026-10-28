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
use Vivarium\Assertion\Composite\Any;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Var\IsInteger;
use Vivarium\Assertion\Var\IsNull;
use Vivarium\Assertion\Var\IsString;

use function preg_quote;

#[CoversClass(Any::class)]
final class AnyTest extends TestCase
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

        (new Any(new IsInteger(), new IsString()))->assert(null, 'Custom {value}.');
    }

    public function testCheckStopsAtFirstPass(): void
    {
        $first = self::createStub(Assertion::class);
        $first->method('check')->willReturn(true);
        $second = $this->createMock(Assertion::class);
        $second->expects(self::never())->method('check');

        self::assertTrue((new Any($first, $second))->check(1));
    }

    public function testCheckNeverAsserts(): void
    {
        $child = $this->createMock(Assertion::class);
        $child->expects(self::never())->method('assert');
        $child->method('check')->willReturn(true);

        (new Any($child))->check(1);
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'int' => [new Any(new IsInteger(), new IsString()), 5, null],
            'string' => [new Any(new IsInteger(), new IsString()), 'a', null],
            'single child' => [new Any(new IsNull()), null, null],
            'none' => [
                new Any(new IsInteger(), new IsString()),
                null,
                'Expected value to satisfy one of [' . IsInteger::class . ', ' . IsString::class . ']. Got null.',
            ],
        ];
    }
}
