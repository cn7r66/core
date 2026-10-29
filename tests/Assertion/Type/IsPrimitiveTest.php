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
use Vivarium\Assertion\Type\IsPrimitive;
use Vivarium\Test\Assertion\Stub\StubChild;
use Vivarium\Type\Types;

use function preg_quote;

#[CoversClass(IsPrimitive::class)]
final class IsPrimitiveTest extends TestCase
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

        (new IsPrimitive())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new IsPrimitive();

        return [
            'keyword' => [$assertion, 'int', null],
            'uppercase' => [$assertion, 'INT', null],
            'mixed' => [$assertion, 'mixed', null],
            'object' => [$assertion, Types::fromString('null'), null],
            'union' => [$assertion, '?int', 'Expected value to be a primitive type. Got "int|null".'],
            'class' => [
                $assertion,
                StubChild::class,
                'Expected value to be a primitive type. Got "' . StubChild::class . '".',
            ],
            'not a type' => [$assertion, 'nope!', 'Expected value to be a type. Got string "nope!".'],
        ];
    }
}
