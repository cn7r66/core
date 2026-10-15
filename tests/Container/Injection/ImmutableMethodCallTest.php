<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Injection;

use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Container;
use Vivarium\Container\Injection\ImmutableMethodCall;
use Vivarium\Container\Provider;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubService;
use Vivarium\Test\Container\Stub\StubWithPrivateMethodInjection;
use Vivarium\Test\Container\Stub\StubWithWrongReturn;

/** @coversDefaultClass \Vivarium\Container\Injection\ImmutableMethodCall */
final class ImmutableMethodCallTest extends TestCase
{
    /**
     * @covers ::__construct
     * @dataProvider provideUncallableMethods
     */
    public function testConstructorThrowsForUncallableMethod(string $class, string $method): void
    {
        static::expectException(AssertionFailed::class);

        new ImmutableMethodCall($class, $method);
    }

    /** @return array<string, array{class-string, string}> */
    public static function provideUncallableMethods(): array
    {
        return [
            'method does not exist' => [StubClass::class, 'nonExistent'],
            'method is private' => [StubWithPrivateMethodInjection::class, 'setService'],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::enhance
     */
    public function testEnhanceReturnsNewInstanceWithDependencyInjected(): void
    {
        $service   = new StubService();
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($service);

        $initialService = new StubService();
        $instance       = new StubClass($initialService);
        $injection      = new ImmutableMethodCall(StubClass::class, 'withService');
        $result         = $injection->enhance($instance, $container);

        static::assertNotSame($instance, $result);
        static::assertInstanceOf(StubClass::class, $result);
        static::assertSame($service, $result->getService());
        static::assertSame($initialService, $instance->getService());
    }

    /**
     * @covers ::__construct
     * @covers ::enhance
     */
    public function testEnhanceFailsWhenInstanceTypeIsWrong(): void
    {
        static::expectException(AssertionFailed::class);

        $container = $this->createMock(Container::class);
        $injection = new ImmutableMethodCall(StubClass::class, 'withService');
        $injection->enhance(new StubService(), $container);
    }

    /**
     * @covers ::__construct
     * @covers ::enhance
     */
    public function testEnhanceFailsWhenMethodReturnsWrongType(): void
    {
        static::expectException(AssertionFailed::class);

        $service   = new StubService();
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($service);

        $injection = new ImmutableMethodCall(StubWithWrongReturn::class, 'withSomething');
        $injection->enhance(new StubWithWrongReturn(), $container);
    }

    /**
     * @covers ::__construct
     * @covers ::accept
     * @dataProvider provideAcceptTargets
     */
    public function testAcceptAnswersWhetherTargetHasMethod(string $target, bool $expected): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn($target);

        static::assertSame(
            $expected,
            (new ImmutableMethodCall(StubClass::class, 'withService'))->accept($provider),
        );
    }

    /** @return array<string, array{string, bool}> */
    public static function provideAcceptTargets(): array
    {
        return [
            'target has the method' => [StubClass::class, true],
            'target lacks the method' => [StubService::class, false],
            'target is not a class' => ['string', false],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::getSlot
     */
    public function testGetSlotReturnsMethodName(): void
    {
        $injection = new ImmutableMethodCall(StubClass::class, 'withService');

        static::assertSame('withService', $injection->getSlot());
    }

    /**
     * @covers ::__construct
     * @covers ::bindArgument
     */
    public function testBindArgument(): void
    {
        $boundService     = new StubService();
        $containerService = new StubService();

        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);
        $provider->method('provide')->willReturn($boundService);

        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($containerService);

        $original = new ImmutableMethodCall(StubClass::class, 'withService');
        $bound    = $original->bindArgument('service')->toProvider($provider);

        static::assertNotSame($original, $bound);

        $instance = new StubClass(new StubService());

        static::assertSame($boundService, $bound->enhance($instance, $container)->getService());
        static::assertSame($containerService, $original->enhance($instance, $container)->getService());
    }

    /**
     * @covers ::__construct
     * @covers ::bindArgumentAtPosition
     */
    public function testBindArgumentAtPosition(): void
    {
        $boundService     = new StubService();
        $containerService = new StubService();

        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);
        $provider->method('provide')->willReturn($boundService);

        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($containerService);

        $original = new ImmutableMethodCall(StubClass::class, 'withService');
        $bound    = $original->bindArgumentAtPosition(0)->toProvider($provider);

        static::assertNotSame($original, $bound);

        $instance = new StubClass(new StubService());

        static::assertSame($boundService, $bound->enhance($instance, $container)->getService());
        static::assertSame($containerService, $original->enhance($instance, $container)->getService());
    }

    /**
     * @dataProvider provideEqualMethodCalls
     * @covers ::__construct
     * @covers ::equals
     */
    public function testEqualsReturnsTrueForEqualMethodCalls(
        ImmutableMethodCall $first,
        ImmutableMethodCall $second,
    ): void {
        static::assertTrue($first->equals($second));
    }

    /**
     * @dataProvider provideEqualMethodCalls
     * @covers ::__construct
     * @covers ::hash
     */
    public function testHashIsSameForEqualMethodCalls(ImmutableMethodCall $first, ImmutableMethodCall $second): void
    {
        static::assertSame($first->hash(), $second->hash());
    }

    /**
     * @dataProvider provideDifferentObjects
     * @covers ::__construct
     * @covers ::equals
     */
    public function testEqualsReturnsFalseForDifferentObjects(ImmutableMethodCall $first, object $second): void
    {
        static::assertFalse($first->equals($second));
    }

    /** @return array<string, array{ImmutableMethodCall, ImmutableMethodCall}> */
    public static function provideEqualMethodCalls(): array
    {
        $reflexive = new ImmutableMethodCall(StubClass::class, 'withService');

        $provider = self::createStub(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);
        $provider->method('provide')->willReturn(new StubService());

        $withoutBinding = new ImmutableMethodCall(StubClass::class, 'withService');
        $withBinding    = $withoutBinding->bindArgument('service')->toProvider($provider);

        return [
            'same instance' => [$reflexive, $reflexive],
            'same class and method' => [
                new ImmutableMethodCall(StubClass::class, 'withService'),
                new ImmutableMethodCall(StubClass::class, 'withService'),
            ],
            'argument bindings excluded from identity' => [$withoutBinding, $withBinding],
        ];
    }

    /** @return array<string, array{ImmutableMethodCall, object}> */
    public static function provideDifferentObjects(): array
    {
        return [
            'same class, different method' => [
                new ImmutableMethodCall(StubClass::class, 'withService'),
                new ImmutableMethodCall(StubClass::class, 'setService'),
            ],
            'different class and method' => [
                new ImmutableMethodCall(StubClass::class, 'withService'),
                new ImmutableMethodCall(StubWithWrongReturn::class, 'withSomething'),
            ],
            'not an ImmutableMethodCall' => [
                new ImmutableMethodCall(StubClass::class, 'withService'),
                new stdClass(),
            ],
        ];
    }
}
