<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Var;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Var\IsNotNull;

use function preg_quote;

#[CoversClass(IsNotNull::class)]
final class IsNotNullTest extends TestCase
{
    #[DataProvider('provideValues')]
    public function testCheck(mixed $value, string|null $message): void
    {
        self::assertSame($message === null, (new IsNotNull())->check($value));
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

        (new IsNotNull())->assert($value);
    }

    #[TestWith([null, 'Custom null.'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsNotNull())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'int zero' => [0, null],
            'empty string' => ['', null],
            'false' => [false, null],
            'empty array' => [[], null],
            'null' => [null, 'Expected value not to be null.'],
        ];
    }
}
