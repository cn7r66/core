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
use Vivarium\Assertion\Type\IsAssignableTo;
use Vivarium\Test\Assertion\Stub\StubChild;
use Vivarium\Test\Assertion\Stub\StubContractA;
use Vivarium\Type\Types;

use function preg_quote;

#[CoversClass(IsAssignableTo::class)]
final class IsAssignableToTest extends TestCase
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

    #[TestWith(['string', 'Custom string "string".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsAssignableTo('int'))->assert($value, 'Custom {value}.');
    }

    #[TestWith(['nope!', 'Expected value to be a type. Got string "nope!".'])]
    public function testConstructorRejectsInvalidTarget(string $target, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        new IsAssignableTo($target);
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new IsAssignableTo('int');

        return [
            'same' => [$assertion, 'int', null],
            'widening to float' => [new IsAssignableTo('float'), 'int', null],
            'null to nullable' => [new IsAssignableTo('?int'), 'null', null],
            'member of union' => [new IsAssignableTo('int|string'), 'int', null],
            'class to interface' => [new IsAssignableTo(StubContractA::class), StubChild::class, null],
            'type object target' => [new IsAssignableTo(Types::fromString('int|string')), 'int', null],
            'type object value' => [$assertion, Types::fromString('int'), null],
            'other primitive' => [$assertion, 'string', 'Expected value to be assignable to "int". Got "string".'],
            'wider union' => [$assertion, 'int|string', 'Expected value to be assignable to "int". Got "int|string".'],
            'nullable' => [$assertion, '?int', 'Expected value to be assignable to "int". Got "int|null".'],
            'not a type' => [$assertion, 'nope!', 'Expected value to be a type. Got string "nope!".'],
        ];
    }
}
