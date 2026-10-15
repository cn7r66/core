<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Decorator;

use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Decorator;
use Vivarium\Container\Decorator\Wrapper;
use Vivarium\Container\Injector;
use Vivarium\Container\Provider;
use Vivarium\Container\Provider\Constructor;
use Vivarium\Container\Provider\Fun;
use Vivarium\Container\Registry\EagerRegistry;
use Vivarium\Test\Container\Stub\StubWrapperDecorator;
use Vivarium\Test\Container\Stub\StubWrapperDependency;
use Vivarium\Test\Container\Stub\StubWrapperInner;
use Vivarium\Test\Container\Stub\StubWrapperSubject;

/** @coversDefaultClass \Vivarium\Container\Decorator\Wrapper */
final class WrapperTest extends TestCase
{
    /** @return array<string, array{string|int}> */
    public static function provideValidSlots(): array
    {
        return [
            'by name' => ['inner'],
            'by position' => [0],
        ];
    }

    /** @return array<string, array{string|int}> */
    public static function provideInvalidSlots(): array
    {
        return [
            'unknown name' => ['missing'],
            'position out of range' => [5],
            'negative position' => [-1],
        ];
    }

    /** @return array<string, array{Provider}> */
    public static function provideProviders(): array
    {
        return [
            'constructor' => [new Constructor(StubWrapperDecorator::class)],
            'function' => [new Fun(static fn (StubWrapperSubject $inner): string => 'x')],
        ];
    }

    /**
     * @dataProvider provideValidSlots
     * @covers ::enhance
     * @covers ::bindSlot
     * @covers ::__construct
     */
    public function testEnhanceBindsInstanceToSlot(string|int $slot): void
    {
        $instance = new StubWrapperInner();
        $wrapper  = new Wrapper(new Constructor(StubWrapperDecorator::class), $slot);

        $result = $wrapper->enhance($instance, new Injector(new EagerRegistry()));

        self::assertInstanceOf(StubWrapperDecorator::class, $result);
        self::assertSame($instance, $result->inner);
        self::assertInstanceOf(StubWrapperDependency::class, $result->dep);
    }

    /**
     * @dataProvider provideValidSlots
     * @covers ::enhance
     * @covers ::bindSlot
     * @covers ::__construct
     */
    public function testEnhanceReturnsFunctionResult(string|int $slot): void
    {
        $instance = new StubWrapperInner();
        $expected = new stdClass();
        $received = [];
        $caller   = new Fun(
            static function (
                StubWrapperSubject $inner,
                StubWrapperDependency $dep,
            ) use (
                $expected,
                &$received,
            ): stdClass {
                $received = [$inner, $dep];

                return $expected;
            },
        );
        $wrapper  = new Wrapper($caller, $slot);

        $result = $wrapper->enhance($instance, new Injector(new EagerRegistry()));

        self::assertSame($expected, $result);
        self::assertSame($instance, $received[0]);
        self::assertInstanceOf(StubWrapperDependency::class, $received[1]);
    }

    /**
     * @covers ::enhance
     * @covers ::bindSlot
     * @covers ::__construct
     */
    public function testEnhanceDifferentInstancesIndependently(): void
    {
        $first     = new StubWrapperInner();
        $second    = new StubWrapperInner();
        $wrapper   = new Wrapper(new Constructor(StubWrapperDecorator::class), 'inner');
        $container = new Injector(new EagerRegistry());

        $firstResult  = $wrapper->enhance($first, $container);
        $secondResult = $wrapper->enhance($second, $container);

        self::assertInstanceOf(StubWrapperDecorator::class, $firstResult);
        self::assertInstanceOf(StubWrapperDecorator::class, $secondResult);
        self::assertSame($first, $firstResult->inner);
        self::assertSame($second, $secondResult->inner);
    }

    /**
     * @dataProvider provideInvalidSlots
     * @covers ::bindSlot
     * @covers ::__construct
     */
    public function testConstructionWithInvalidSlotThrows(string|int $slot): void
    {
        $this->expectException(AssertionFailed::class);

        new Wrapper(new Constructor(StubWrapperDecorator::class), $slot);
    }

    /**
     * @covers ::enhance
     * @covers ::bindSlot
     * @covers ::__construct
     */
    public function testEnhanceRejectsInstanceNotAssignableToSlot(): void
    {
        $wrapper = new Wrapper(new Constructor(StubWrapperDecorator::class), 'inner');

        $this->expectException(AssertionFailed::class);

        $wrapper->enhance(new stdClass(), new Injector(new EagerRegistry()));
    }

    /** @covers ::__construct */
    public function testIsDecorator(): void
    {
        $wrapper = new Wrapper(new Constructor(StubWrapperDecorator::class), 'inner');

        self::assertInstanceOf(Decorator::class, $wrapper);
    }

    /**
     * @dataProvider provideProviders
     * @covers ::accept
     * @covers ::__construct
     */
    public function testAcceptsAnyProvider(Provider $provider): void
    {
        $wrapper = new Wrapper(new Constructor(StubWrapperDecorator::class), 'inner');

        self::assertTrue($wrapper->accept($provider));
    }
}
