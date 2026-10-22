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
use Vivarium\Check\CheckIfNumber;

#[CoversClass(CheckIfNumber::class)]
final class CheckIfNumberTest extends CheckTestCase
{
    public const string NAMESPACE = 'Vivarium\Test\Assertion\Numeric';

    #[DataProvider('provideMethods')]
    public function testCallStatic(string $method): void
    {
        $this->doTest(
            CheckIfNumber::class,
            $method,
            self::NAMESPACE,
        );
    }

    /** @return array<array<string>> */
    public static function provideMethods(): array
    {
        return [
            ['isGreaterOrEqualThan'],
            ['isGreaterThan'],
            ['isInClosedRange'],
            ['isInHalfOpenLeftRange'],
            ['isInHalfOpenRightRange'],
            ['isInOpenRange'],
            ['isLessOrEqualThan'],
            ['isLessThan'],
            ['isOutOfClosedRange'],
            ['isOutOfOpenRange'],
        ];
    }
}
