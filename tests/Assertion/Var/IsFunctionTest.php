<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Var;

use ArrayObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Var\IsFunction;

use function preg_quote;
use function strlen;

#[CoversClass(IsFunction::class)]
final class IsFunctionTest extends TestCase
{
    #[DataProvider('provideValues')]
    public function testCheck(mixed $value, string|null $message): void
    {
        self::assertSame($message === null, (new IsFunction())->check($value));
    }

    #[DataProvider('provideValues')]
    public function testAssert(mixed $value, string|null $message): void
    {
        if ($message === null) {
            $this->expectNotToPerformAssertions();
        }

        if ($message !== null) {
            $this->expectException(AssertionFailed::class);
            $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');
        }

        (new IsFunction())->assert($value);
    }

    #[TestWith([42, 'Custom int 42.'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsFunction())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'function name' => ['strlen', null],
            'closure' => [static fn (): int => 1, null],
            'first-class callable' => [strlen(...), null],
            'unknown function' => [
                'not_a_function',
                'Expected value to be a Closure or a function name. Got string "not_a_function".',
            ],
            'method array' => [
                [new ArrayObject(), 'count'],
                'Expected value to be a Closure or a function name. Got array(2) [ArrayObject, "count"].',
            ],
            'object' => [
                new ArrayObject(),
                'Expected value to be a Closure or a function name. Got object ArrayObject.',
            ],
        ];
    }
}
