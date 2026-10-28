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
use Vivarium\Assertion\Var\IsObject;
use Vivarium\Test\Assertion\Stub\StubEnum;

use function preg_quote;

#[CoversClass(IsObject::class)]
final class IsObjectTest extends TestCase
{
    #[DataProvider('provideValues')]
    public function testCheck(mixed $value, string|null $message): void
    {
        self::assertSame($message === null, (new IsObject())->check($value));
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

        (new IsObject())->assert($value);
    }

    #[TestWith(['ArrayObject', 'Custom string "ArrayObject".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsObject())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'object' => [new ArrayObject(), null],
            'closure' => [static fn (): int => 1, null],
            'enum case' => [StubEnum::First, null],
            'class name' => ['ArrayObject', 'Expected value to be object. Got string "ArrayObject".'],
            'array' => [[], 'Expected value to be object. Got array(0) [].'],
            'null' => [null, 'Expected value to be object. Got null.'],
        ];
    }
}
