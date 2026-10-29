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
use Vivarium\Assertion\Type\IsNullable;
use Vivarium\Test\Assertion\Stub\StubContractA;
use Vivarium\Type\Types;

use function preg_quote;

#[CoversClass(IsNullable::class)]
final class IsNullableTest extends TestCase
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

    #[TestWith(['int|string', 'Custom string "int|string".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsNullable())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new IsNullable();

        return [
            'nullable shorthand' => [$assertion, '?int', null],
            'union with null' => [$assertion, 'int|null', null],
            'mixed' => [$assertion, 'mixed', null],
            'null' => [$assertion, 'null', null],
            'object' => [$assertion, Types::fromString('?string'), null],
            'primitive' => [$assertion, 'int', 'Expected value to be a nullable type. Got "int".'],
            'class' => [
                $assertion,
                StubContractA::class,
                'Expected value to be a nullable type. Got "' . StubContractA::class . '".',
            ],
            'not a type' => [$assertion, 'nope!', 'Expected value to be a type. Got string "nope!".'],
        ];
    }
}
