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
use Vivarium\Assertion\Var\IsCallable;

use function preg_quote;

#[CoversClass(IsCallable::class)]
final class IsCallableTest extends TestCase
{
    #[DataProvider('provideValues')]
    public function testCheck(mixed $value, string|null $message): void
    {
        self::assertSame($message === null, (new IsCallable())->check($value));
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

        (new IsCallable())->assert($value);
    }

    #[TestWith([42, 'Custom int 42.'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsCallable())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'function name' => ['strlen', null],
            'closure' => [static fn (): int => 1, null],
            'method array' => [[new ArrayObject(), 'count'], null],
            'unknown function' => ['not_a_function', 'Expected value to be callable. Got string "not_a_function".'],
            'unknown method' => [
                [new ArrayObject(), 'nope'],
                'Expected value to be callable. Got array(2) [ArrayObject, "nope"].',
            ],
            'int' => [42, 'Expected value to be callable. Got int 42.'],
        ];
    }
}
