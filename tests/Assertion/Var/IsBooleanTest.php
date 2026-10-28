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
use Vivarium\Assertion\Var\IsBoolean;

use function preg_quote;

#[CoversClass(IsBoolean::class)]
final class IsBooleanTest extends TestCase
{
    #[DataProvider('provideValues')]
    public function testCheck(mixed $value, string|null $message): void
    {
        self::assertSame($message === null, (new IsBoolean())->check($value));
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

        (new IsBoolean())->assert($value);
    }

    #[TestWith([0, 'Custom int 0.'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsBoolean())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'true' => [true, null],
            'false' => [false, null],
            'int zero' => [0, 'Expected value to be bool. Got int 0.'],
            'string true' => ['true', 'Expected value to be bool. Got string "true".'],
            'null' => [null, 'Expected value to be bool. Got null.'],
        ];
    }
}
