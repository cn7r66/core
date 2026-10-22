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
use Vivarium\Check\CheckIfString;

#[CoversClass(CheckIfString::class)]
final class CheckIfStringTest extends CheckTestCase
{
    public const NAMESPACE = 'Vivarium\Test\Assertion\String';

    #[DataProvider('provideMethods')]
    public function testCallStatic(string $method): void
    {
        $this->doTest(
            CheckIfString::class,
            $method,
            self::NAMESPACE,
        );
    }

    /** @return array<array<string>> */
    public static function provideMethods(): array
    {
        return [
            ['contains'],
            ['endsWith'],
            ['isEmpty'],
            ['isLong'],
            ['isLongAtLeast'],
            ['isLongAtMax'],
            ['isLongBetween'],
            ['isNotEmpty'],
            ['startsWith'],
        ];
    }
}
