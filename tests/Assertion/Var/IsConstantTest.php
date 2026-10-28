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
use Vivarium\Assertion\Var\IsConstant;

use function preg_quote;

#[CoversClass(IsConstant::class)]
final class IsConstantTest extends TestCase
{
    #[DataProvider('provideValues')]
    public function testCheck(mixed $value, string|null $message): void
    {
        self::assertSame($message === null, (new IsConstant())->check($value));
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

        (new IsConstant())->assert($value);
    }

    #[TestWith(['NOPE', 'Custom string "NOPE".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsConstant())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'global constant' => ['PHP_EOL', null],
            'error level' => ['E_ALL', null],
            'class constant' => ['ArrayObject::STD_PROP_LIST', null],
            'unknown constant' => [
                'NOT_A_CONSTANT',
                'Expected value to be a defined constant name. Got string "NOT_A_CONSTANT".',
            ],
            'unknown class constant' => [
                'ArrayObject::NOPE',
                'Expected value to be a defined constant name. Got string "ArrayObject::NOPE".',
            ],
            'int' => [42, 'Expected value to be string. Got int 42.'],
            'null' => [null, 'Expected value to be string. Got null.'],
        ];
    }
}
