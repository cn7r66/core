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
use Vivarium\Assertion\Type\IsUnion;
use Vivarium\Type\Types;

use function preg_quote;

#[CoversClass(IsUnion::class)]
final class IsUnionTest extends TestCase
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

    #[TestWith(['int', 'Custom string "int".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsUnion())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new IsUnion();

        return [
            'union' => [$assertion, 'int|string', null],
            'nullable shorthand' => [$assertion, '?int', null],
            'dnf' => [$assertion, '(Countable&Traversable)|null', null],
            'object' => [$assertion, Types::fromString('int|null'), null],
            'primitive' => [$assertion, 'INT', 'Expected value to be a union type. Got "int".'],
            'intersection' => [
                $assertion,
                'Countable&Traversable',
                'Expected value to be a union type. Got "Countable&Traversable".',
            ],
            'primitive object' => [
                $assertion,
                Types::fromString('int'),
                'Expected value to be a union type. Got "int".',
            ],
            'not a type' => [$assertion, 'nope!', 'Expected value to be a type. Got string "nope!".'],
        ];
    }
}
