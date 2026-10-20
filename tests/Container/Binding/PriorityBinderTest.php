<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Binding;

use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Comparator\Priority;
use Vivarium\Container\Binding\PriorityBinder;

/** @coversDefaultClass \Vivarium\Container\Binding\PriorityBinder */
final class PriorityBinderTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::withDefaultPriority
     * @dataProvider defaultPriorityProvider
     */
    public function testWithDefaultPriorityPassesTheDefaultGivenAtConstruction(int $defaultPriority): void
    {
        $capturedPriority = null;

        $binder = new PriorityBinder(
            static function (int $priority) use (&$capturedPriority): string {
                $capturedPriority = $priority;

                return 'result';
            },
            $defaultPriority,
        );

        $binder->withDefaultPriority();

        static::assertSame($defaultPriority, $capturedPriority);
    }

    /** @return array<string, array{int}> */
    public static function defaultPriorityProvider(): array
    {
        return [
            'low' => [Priority::LOW],
            'high' => [Priority::HIGH],
        ];
    }

    /** @covers ::withDefaultPriority */
    public function testWithDefaultPriorityReturnsValueFromCreate(): void
    {
        $expected = new stdClass();

        $binder = new PriorityBinder(
            static fn (int $priority): object => $expected,
            Priority::NORMAL,
        );

        static::assertSame($expected, $binder->withDefaultPriority());
    }

    /** @covers ::withPriority */
    public function testWithPriorityPassesExplicitPriorityToCreate(): void
    {
        $capturedPriority = null;

        $binder = new PriorityBinder(
            static function (int $priority) use (&$capturedPriority): string {
                $capturedPriority = $priority;

                return 'result';
            },
            Priority::NORMAL,
        );

        $binder->withPriority(42);

        static::assertSame(42, $capturedPriority);
    }

    /** @covers ::withPriority */
    public function testWithPriorityReturnsValueFromCreate(): void
    {
        $expected = new stdClass();

        $binder = new PriorityBinder(
            static fn (int $priority): object => $expected,
            Priority::NORMAL,
        );

        static::assertSame($expected, $binder->withPriority(10));
    }
}
