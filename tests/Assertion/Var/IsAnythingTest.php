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
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Var\IsAnything;
use Vivarium\Test\Assertion\Stub\StubEnum;

use function fopen;

use const NAN;

#[CoversClass(IsAnything::class)]
final class IsAnythingTest extends TestCase
{
    #[DataProvider('provideValues')]
    public function testCheck(mixed $value): void
    {
        self::assertTrue((new IsAnything())->check($value));
    }

    #[DataProvider('provideValues')]
    public function testAssert(mixed $value): void
    {
        $this->expectNotToPerformAssertions();

        (new IsAnything())->assert($value);
    }

    /** @return array<string, array{mixed}> */
    public static function provideValues(): array
    {
        return [
            'null' => [null],
            'bool' => [false],
            'int' => [0],
            'float not a number' => [NAN],
            'string' => [''],
            'array' => [[]],
            'object' => [new ArrayObject()],
            'closure' => [static fn (): int => 1],
            'enum case' => [StubEnum::First],
            'resource' => [fopen('php://memory', 'r')],
        ];
    }
}
