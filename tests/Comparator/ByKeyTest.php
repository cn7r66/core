<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Comparator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Comparator\ByKey;
use Vivarium\Comparator\Comparator;

#[CoversClass(ByKey::class)]
final class ByKeyTest extends TestCase
{
    public function testCompare(): void
    {
        $order = $this->createMock(Comparator::class);
        $order->expects(self::once())
            ->method('compare')
            ->with(30, 25)
            ->willReturn(1);

        $byAge = new ByKey(
            static function (array $person): mixed {
                return $person['age'];
            },
            $order,
        );

        self::assertSame(1, $byAge->compare(['age' => 30], ['age' => 25]) <=> 0);
    }
}
