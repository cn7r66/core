<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Check;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Vivarium\Check\CheckIfVar;

#[CoversClass(CheckIfVar::class)]
final class CheckIfVarTest extends CheckTestCase
{
    public const NAMESPACE = 'Vivarium\Test\Assertion\Var';

    #[DataProvider('provideMethods')]
    public function testCallStatic(string $method): void
    {
        $this->doTest(
            CheckIfVar::class,
            $method,
            self::NAMESPACE,
        );
    }

    /** @return array<array<string>> */
    public static function provideMethods(): array
    {
        return [
            ['isArray'],
            ['isBoolean'],
            ['isCallable'],
            ['isFloat'],
            ['isInteger'],
            ['isNumeric'],
            ['isObject'],
            ['isString'],
        ];
    }
}
