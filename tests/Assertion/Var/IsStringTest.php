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
use Vivarium\Assertion\Var\IsString;
use Vivarium\Test\Assertion\Stub\StubEnum;

use function preg_quote;

#[CoversClass(IsString::class)]
final class IsStringTest extends TestCase
{
    #[DataProvider('provideValues')]
    public function testCheck(mixed $value, string|null $message): void
    {
        self::assertSame($message === null, (new IsString())->check($value));
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

        (new IsString())->assert($value);
    }

    #[TestWith([1, 'Custom int 1.'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsString())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'string' => ['abc', null],
            'empty string' => ['', null],
            'numeric string' => ['1', null],
            'int' => [1, 'Expected value to be string. Got int 1.'],
            'null' => [null, 'Expected value to be string. Got null.'],
            'object' => [new ArrayObject(), 'Expected value to be string. Got object ArrayObject.'],
            'enum case' => [StubEnum::First, 'Expected value to be string. Got enum ' . StubEnum::class . '::First.'],
        ];
    }
}
