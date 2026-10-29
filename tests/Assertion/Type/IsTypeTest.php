<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Type\IsType;
use Vivarium\Test\Assertion\Stub\StubChild;
use Vivarium\Type\Types;

use function preg_quote;

#[CoversClass(IsType::class)]
final class IsTypeTest extends TestCase
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

    #[TestWith(['self', 'Custom string "self".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsType())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new IsType();

        return [
            'primitive' => [$assertion, 'int', null],
            'nullable shorthand' => [$assertion, '?int', null],
            'uppercase' => [$assertion, 'INT', null],
            'dnf' => [$assertion, '(Countable&Traversable)|null', null],
            'class name' => [$assertion, StubChild::class, null],
            'type object' => [$assertion, Types::fromString('int|string'), null],
            'contextual name' => [$assertion, 'self', 'Expected value to be a type. Got string "self".'],
            'invalid' => [$assertion, 'nope!', 'Expected value to be a type. Got string "nope!".'],
            'empty' => [$assertion, '', 'Expected value to be a type. Got string "".'],
            'int' => [$assertion, 1, 'Expected value to be a type. Got int 1.'],
            'null' => [$assertion, null, 'Expected value to be a type. Got null.'],
        ];
    }
}
