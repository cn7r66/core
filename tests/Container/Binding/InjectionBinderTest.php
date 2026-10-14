<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Binding;

use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Comparator\Priority;
use Vivarium\Container\Binding;
use Vivarium\Container\Binding\InjectionBinder;
use Vivarium\Container\Binding\PriorityBinder;
use Vivarium\Container\Injection;
use Vivarium\Container\Injection\ImmutableMethodCall;
use Vivarium\Container\Injection\MethodCall;
use Vivarium\Container\Injection\SetProperty;
use Vivarium\Test\Container\Stub\StubBinderContract;
use Vivarium\Test\Container\Stub\StubClass;

/** @coversDefaultClass \Vivarium\Container\Binding\InjectionBinder */
final class InjectionBinderTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::onProperty
     */
    public function testOnPropertyReturnsPriorityBinder(): void
    {
        $binder = new InjectionBinder(
            new Binding(StubClass::class),
            static fn (Injection $injection, int $priority): string => 'result',
            Priority::NORMAL,
        );

        static::assertInstanceOf(PriorityBinder::class, $binder->onProperty('property'));
    }

    /** @covers ::onProperty */
    public function testOnPropertyBuildsSetPropertyInjection(): void
    {
        $binder = new InjectionBinder(
            new Binding(StubClass::class),
            function (Injection $injection, int $priority): string {
                $this->assertInstanceOf(SetProperty::class, $injection);

                return 'result';
            },
            Priority::NORMAL,
        );

        $binder->onProperty('property')->withDefaultPriority();
    }

    /** @covers ::onProperty */
    public function testOnPropertyInfersTypeFromPropertyWhenNotProvided(): void
    {
        $capturedInjection = null;

        $binder = new InjectionBinder(
            new Binding(StubClass::class),
            static function (Injection $injection, int $priority) use (&$capturedInjection): string {
                $capturedInjection = $injection;

                return 'result';
            },
            Priority::NORMAL,
        );

        $binder->onProperty('property')->withDefaultPriority();

        static::assertInstanceOf(SetProperty::class, $capturedInjection);
    }

    /** @covers ::onProperty */
    public function testOnPropertyUsesExplicitTypeWhenProvided(): void
    {
        $capturedInjection = null;

        $binder = new InjectionBinder(
            new Binding(StubClass::class),
            static function (Injection $injection, int $priority) use (&$capturedInjection): string {
                $capturedInjection = $injection;

                return 'result';
            },
            Priority::NORMAL,
        );

        $binder->onProperty('property', 'string')->withDefaultPriority();

        static::assertInstanceOf(SetProperty::class, $capturedInjection);
    }

    /** @covers ::onProperty */
    public function testOnPropertyUsesExplicitTagWhenProvided(): void
    {
        $capturedInjection = null;

        $binder = new InjectionBinder(
            new Binding(StubClass::class),
            static function (Injection $injection, int $priority) use (&$capturedInjection): string {
                $capturedInjection = $injection;

                return 'result';
            },
            Priority::NORMAL,
        );

        $binder->onProperty('property', Binding::DEFAULT, 'myTag')->withDefaultPriority();

        static::assertInstanceOf(SetProperty::class, $capturedInjection);
    }

    /** @covers ::onProperty */
    public function testOnPropertyThrowsWhenTypeIsNotAClass(): void
    {
        $this->expectException(AssertionFailed::class);

        $binder = new InjectionBinder(
            new Binding('string'),
            static function (Injection $injection, int $priority): void {
            },
            Priority::NORMAL,
        );

        $binder->onProperty('property');
    }

    /** @covers ::onProperty */
    public function testOnPropertyThrowsWhenPropertyDoesNotExist(): void
    {
        $this->expectException(AssertionFailed::class);

        $binder = new InjectionBinder(
            new Binding(StubClass::class),
            static function (Injection $injection, int $priority): void {
            },
            Priority::NORMAL,
        );

        $binder->onProperty('nonExistentProperty');
    }

    /** @covers ::onMethod */
    public function testOnMethodReturnsPriorityBinder(): void
    {
        $binder = new InjectionBinder(
            new Binding(StubClass::class),
            static fn (Injection $injection, int $priority): string => 'result',
            Priority::NORMAL,
        );

        static::assertInstanceOf(PriorityBinder::class, $binder->onMethod('setService'));
    }

    /** @covers ::onMethod */
    public function testOnMethodBuildsMethodCallInjection(): void
    {
        $binder = new InjectionBinder(
            new Binding(StubClass::class),
            function (Injection $injection, int $priority): string {
                $this->assertInstanceOf(MethodCall::class, $injection);

                return 'result';
            },
            Priority::NORMAL,
        );

        $binder->onMethod('setService')->withDefaultPriority();
    }

    /** @covers ::onMethod */
    public function testOnMethodThrowsWhenTypeIsNotAClass(): void
    {
        $this->expectException(AssertionFailed::class);

        $binder = new InjectionBinder(
            new Binding('string'),
            static function (Injection $injection, int $priority): void {
            },
            Priority::NORMAL,
        );

        $binder->onMethod('setService');
    }

    /** @covers ::onMethod */
    public function testOnMethodThrowsWhenMethodDoesNotExist(): void
    {
        $this->expectException(AssertionFailed::class);

        $binder = new InjectionBinder(
            new Binding(StubClass::class),
            static function (Injection $injection, int $priority): void {
            },
            Priority::NORMAL,
        );

        $binder->onMethod('nonExistentMethod');
    }

    /** @covers ::onImmutableMethod */
    public function testOnImmutableMethodReturnsPriorityBinder(): void
    {
        $binder = new InjectionBinder(
            new Binding(StubClass::class),
            static fn (Injection $injection, int $priority): string => 'result',
            Priority::NORMAL,
        );

        static::assertInstanceOf(PriorityBinder::class, $binder->onImmutableMethod('withService'));
    }

    /** @covers ::onImmutableMethod */
    public function testOnImmutableMethodBuildsImmutableMethodCallInjection(): void
    {
        $binder = new InjectionBinder(
            new Binding(StubClass::class),
            function (Injection $injection, int $priority): string {
                $this->assertInstanceOf(ImmutableMethodCall::class, $injection);

                return 'result';
            },
            Priority::NORMAL,
        );

        $binder->onImmutableMethod('withService')->withDefaultPriority();
    }

    /** @covers ::onImmutableMethod */
    public function testOnImmutableMethodThrowsWhenTypeIsNotAClass(): void
    {
        $this->expectException(AssertionFailed::class);

        $binder = new InjectionBinder(
            new Binding('string'),
            static function (Injection $injection, int $priority): void {
            },
            Priority::NORMAL,
        );

        $binder->onImmutableMethod('withService');
    }

    /** @covers ::onImmutableMethod */
    public function testOnImmutableMethodThrowsWhenMethodDoesNotExist(): void
    {
        $this->expectException(AssertionFailed::class);

        $binder = new InjectionBinder(
            new Binding(StubClass::class),
            static function (Injection $injection, int $priority): void {
            },
            Priority::NORMAL,
        );

        $binder->onImmutableMethod('nonExistentMethod');
    }

    /** @covers ::withInjection */
    public function testWithInjectionReturnsPriorityBinder(): void
    {
        $binder = new InjectionBinder(
            new Binding(StubClass::class),
            static fn (Injection $inj, int $priority): string => 'result',
            Priority::NORMAL,
        );

        $result = $binder->withInjection($this->createMock(Injection::class));
        static::assertInstanceOf(PriorityBinder::class, $result);
    }

    /** @covers ::withInjection */
    public function testWithInjectionForwardsInjectionToCreate(): void
    {
        $injection = $this->createMock(Injection::class);

        $binder = new InjectionBinder(
            new Binding(StubClass::class),
            function (Injection $inj, int $priority) use ($injection): string {
                $this->assertSame($injection, $inj);

                return 'result';
            },
            Priority::NORMAL,
        );

        $binder->withInjection($injection)->withDefaultPriority();
    }

    /**
     * @covers ::onMethod
     * @covers ::withInjection
     * @covers ::__construct
     */
    public function testOnMethodAcceptsAnInterface(): void
    {
        $binder = new InjectionBinder(
            new Binding(StubBinderContract::class),
            static fn (Injection $injection, int $priority): string => 'result',
            Priority::NORMAL,
        );

        static::assertInstanceOf(PriorityBinder::class, $binder->onMethod('configure'));
    }

    /**
     * @covers ::onImmutableMethod
     * @covers ::withInjection
     * @covers ::__construct
     */
    public function testOnImmutableMethodAcceptsAnInterface(): void
    {
        $binder = new InjectionBinder(
            new Binding(StubBinderContract::class),
            static fn (Injection $injection, int $priority): string => 'result',
            Priority::NORMAL,
        );

        static::assertInstanceOf(PriorityBinder::class, $binder->onImmutableMethod('configure'));
    }

    /**
     * @covers ::onProperty
     * @covers ::__construct
     */
    public function testOnPropertyRejectsAnInterface(): void
    {
        $this->expectException(AssertionFailed::class);

        $binder = new InjectionBinder(
            new Binding(StubBinderContract::class),
            static fn (Injection $injection, int $priority): string => 'result',
            Priority::NORMAL,
        );

        $binder->onProperty('property');
    }
}
