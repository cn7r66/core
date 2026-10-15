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

    /**
     * @covers ::onProperty
     * @dataProvider providePropertyTypesAndTags
     */
    public function testOnPropertyBuildsSetPropertyForTypeAndTag(string $type, string $tag): void
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

        $binder->onProperty('property', $type, $tag)->withDefaultPriority();

        static::assertInstanceOf(SetProperty::class, $capturedInjection);
    }

    /** @return array<string, array{string, string}> */
    public static function providePropertyTypesAndTags(): array
    {
        return [
            'type inferred from property' => [Binding::DEFAULT, Binding::DEFAULT],
            'explicit type' => ['string', Binding::DEFAULT],
            'explicit tag' => [Binding::DEFAULT, 'myTag'],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::onProperty
     * @dataProvider provideInvalidPropertyTargets
     */
    public function testOnPropertyThrowsForInvalidTarget(string $type, string $property): void
    {
        $this->expectException(AssertionFailed::class);

        $binder = new InjectionBinder(
            new Binding($type),
            static function (Injection $injection, int $priority): void {
            },
            Priority::NORMAL,
        );

        $binder->onProperty($property);
    }

    /** @return array<string, array{string, string}> */
    public static function provideInvalidPropertyTargets(): array
    {
        return [
            'type is not a class' => ['string', 'property'],
            'property does not exist' => [StubClass::class, 'nonExistentProperty'],
            'type is an interface' => [StubBinderContract::class, 'property'],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::onMethod
     * @covers ::withInjection
     * @dataProvider provideMethodDeclarers
     */
    public function testOnMethodReturnsPriorityBinderForClassOrInterface(string $type, string $method): void
    {
        $binder = new InjectionBinder(
            new Binding($type),
            static fn (Injection $injection, int $priority): string => 'result',
            Priority::NORMAL,
        );

        static::assertInstanceOf(PriorityBinder::class, $binder->onMethod($method));
    }

    /** @return array<string, array{class-string, string}> */
    public static function provideMethodDeclarers(): array
    {
        return [
            'class' => [StubClass::class, 'setService'],
            'interface' => [StubBinderContract::class, 'configure'],
        ];
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

    /**
     * @covers ::onMethod
     * @dataProvider provideInvalidMethodTargets
     */
    public function testOnMethodThrowsForInvalidTarget(string $type, string $method): void
    {
        $this->expectException(AssertionFailed::class);

        $binder = new InjectionBinder(
            new Binding($type),
            static function (Injection $injection, int $priority): void {
            },
            Priority::NORMAL,
        );

        $binder->onMethod($method);
    }

    /** @return array<string, array{string, string}> */
    public static function provideInvalidMethodTargets(): array
    {
        return [
            'type is not a class' => ['string', 'setService'],
            'method does not exist' => [StubClass::class, 'nonExistentMethod'],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::onImmutableMethod
     * @covers ::withInjection
     * @dataProvider provideImmutableMethodDeclarers
     */
    public function testOnImmutableMethodReturnsPriorityBinderForClassOrInterface(string $type, string $method): void
    {
        $binder = new InjectionBinder(
            new Binding($type),
            static fn (Injection $injection, int $priority): string => 'result',
            Priority::NORMAL,
        );

        static::assertInstanceOf(PriorityBinder::class, $binder->onImmutableMethod($method));
    }

    /** @return array<string, array{class-string, string}> */
    public static function provideImmutableMethodDeclarers(): array
    {
        return [
            'class' => [StubClass::class, 'withService'],
            'interface' => [StubBinderContract::class, 'configure'],
        ];
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

    /**
     * @covers ::onImmutableMethod
     * @dataProvider provideInvalidImmutableMethodTargets
     */
    public function testOnImmutableMethodThrowsForInvalidTarget(string $type, string $method): void
    {
        $this->expectException(AssertionFailed::class);

        $binder = new InjectionBinder(
            new Binding($type),
            static function (Injection $injection, int $priority): void {
            },
            Priority::NORMAL,
        );

        $binder->onImmutableMethod($method);
    }

    /** @return array<string, array{string, string}> */
    public static function provideInvalidImmutableMethodTargets(): array
    {
        return [
            'type is not a class' => ['string', 'withService'],
            'method does not exist' => [StubClass::class, 'nonExistentMethod'],
        ];
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
}
