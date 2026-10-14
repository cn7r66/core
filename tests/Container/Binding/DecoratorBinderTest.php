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
use Vivarium\Container\Binding\DecoratorBinder;
use Vivarium\Container\Binding\PriorityBinder;
use Vivarium\Container\Decorator;

/** @coversDefaultClass \Vivarium\Container\Binding\DecoratorBinder */
final class DecoratorBinderTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::withDecorator
     */
    public function testWithDecoratorReturnsPriorityBinder(): void
    {
        $binder = new DecoratorBinder(
            static fn (Decorator $dec, int $priority): string => 'result',
            Priority::NORMAL,
        );

        static::assertInstanceOf(PriorityBinder::class, $binder->withDecorator($this->createMock(Decorator::class)));
    }

    /**
     * @covers ::withDecorator
     * @covers \Vivarium\Container\Binding\PriorityBinder::withDefaultPriority
     */
    public function testWithDecoratorForwardsDecoratorToCreate(): void
    {
        $decorator = $this->createMock(Decorator::class);
        $captured  = null;

        $binder = new DecoratorBinder(
            static function (Decorator $dec, int $priority) use (&$captured): string {
                $captured = $dec;

                return 'result';
            },
            Priority::NORMAL,
        );

        $binder->withDecorator($decorator)->withDefaultPriority();

        static::assertSame($decorator, $captured);
    }

    /**
     * @covers ::withDecorator
     * @covers \Vivarium\Container\Binding\PriorityBinder::withDefaultPriority
     * @dataProvider defaultPriorityProvider
     */
    public function testWithDefaultPriorityPassesTheDefaultGivenAtConstruction(int $defaultPriority): void
    {
        $capturedPriority = null;

        $binder = new DecoratorBinder(
            static function (Decorator $dec, int $priority) use (&$capturedPriority): int {
                $capturedPriority = $priority;

                return $priority;
            },
            $defaultPriority,
        );

        $binder->withDecorator($this->createMock(Decorator::class))->withDefaultPriority();

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

    /**
     * @covers ::withDecorator
     * @covers \Vivarium\Container\Binding\PriorityBinder::withPriority
     */
    public function testWithPriorityPassesExplicitPriorityToCreate(): void
    {
        $capturedPriority = null;

        $binder = new DecoratorBinder(
            static function (Decorator $dec, int $priority) use (&$capturedPriority): int {
                $capturedPriority = $priority;

                return $priority;
            },
            Priority::NORMAL,
        );

        $binder->withDecorator($this->createMock(Decorator::class))->withPriority(42);

        static::assertSame(42, $capturedPriority);
    }

    /**
     * @covers ::withDecorator
     * @covers \Vivarium\Container\Binding\PriorityBinder::withDefaultPriority
     */
    public function testWithDefaultPriorityReturnsValueFromCreate(): void
    {
        $expected = new stdClass();

        $binder = new DecoratorBinder(
            static fn (Decorator $dec, int $priority): object => $expected,
            Priority::NORMAL,
        );

        $result = $binder->withDecorator($this->createMock(Decorator::class))->withDefaultPriority();

        static::assertSame($expected, $result);
    }

    /**
     * @covers ::withDecorator
     * @covers \Vivarium\Container\Binding\PriorityBinder::withPriority
     */
    public function testWithPriorityReturnsValueFromCreate(): void
    {
        $expected = new stdClass();

        $binder = new DecoratorBinder(
            static fn (Decorator $dec, int $priority): object => $expected,
            Priority::NORMAL,
        );

        $result = $binder->withDecorator($this->createMock(Decorator::class))->withPriority(42);

        static::assertSame($expected, $result);
    }
}
