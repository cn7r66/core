<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Injection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Container;
use Vivarium\Container\Injection\MethodCall;
use Vivarium\Container\Provider;
use Vivarium\Test\Container\Stub\Stub;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubMethodCallBase;
use Vivarium\Test\Container\Stub\StubMethodCallSubclass;
use Vivarium\Test\Container\Stub\StubService;
use Vivarium\Test\Container\Stub\StubWithPrivateMethodInjection;

#[CoversClass(MethodCall::class)]
final class MethodCallTest extends TestCase
{
    #[DataProvider('provideUncallableMethods')]
    public function testConstructorThrowsForUncallableMethod(string $class, string $method): void
    {
        static::expectException(AssertionFailed::class);

        new MethodCall($class, $method);
    }

    /** @return array<string, array{class-string, string}> */
    public static function provideUncallableMethods(): array
    {
        return [
            'method does not exist' => [StubClass::class, 'nonExistent'],
            'method is private' => [StubWithPrivateMethodInjection::class, 'setService'],
            'method not declared by the interface' => [Stub::class, 'setService'],
        ];
    }

    public function testEnhanceCallsMethodAndReturnsSameInstance(): void
    {
        $service   = new StubService();
        $container = $this->createStub(Container::class);
        $container->method('get')->willReturn($service);

        $instance  = new StubClass(new StubService());
        $injection = new MethodCall(StubClass::class, 'setService');
        $result    = $injection->enhance($instance, $container);

        static::assertSame($instance, $result);
        static::assertSame($service, $instance->getService());
    }

    public function testEnhanceFailsWhenInstanceTypeIsWrong(): void
    {
        static::expectException(AssertionFailed::class);

        $container = $this->createStub(Container::class);
        $injection = new MethodCall(StubClass::class, 'setService');
        $injection->enhance(new StubService(), $container);
    }

    #[DataProvider('provideAcceptTargets')]
    public function testAcceptAnswersWhetherTargetHasMethod(string $target, bool $expected): void
    {
        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn($target);

        static::assertSame(
            $expected,
            (new MethodCall(StubClass::class, 'setService'))->accept($provider),
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

    public function testGetSlotReturnsMethodName(): void
    {
        $injection = new MethodCall(StubClass::class, 'setService');

        static::assertSame('setService', $injection->getSlot());
    }

    public function testBindArgument(): void
    {
        $boundService = new StubService();
        $provider     = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);
        $provider->method('provide')->willReturn($boundService);

        $original = new MethodCall(StubClass::class, 'setService');
        $bound    = $original->bindArgument('service')->toProvider($provider);

        static::assertNotSame($original, $bound);

        $resolvedService = new StubService();
        $container       = $this->createStub(Container::class);
        $container->method('get')->willReturn($resolvedService);

        $boundInstance = new StubClass(new StubService());
        $bound->enhance($boundInstance, $container);
        static::assertSame($boundService, $boundInstance->getService());

        $originalInstance = new StubClass(new StubService());
        $original->enhance($originalInstance, $container);
        static::assertSame($resolvedService, $originalInstance->getService());
    }

    public function testBindArgumentAtPosition(): void
    {
        $boundService = new StubService();
        $provider     = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);
        $provider->method('provide')->willReturn($boundService);

        $original = new MethodCall(StubClass::class, 'setService');
        $bound    = $original->bindArgumentAtPosition(0)->toProvider($provider);

        static::assertNotSame($original, $bound);

        $container = $this->createStub(Container::class);
        $instance  = new StubClass(new StubService());

        $bound->enhance($instance, $container);

        static::assertSame($boundService, $instance->getService());
    }

    public function testEnhanceProjection(): void
    {
        $boundService = new StubService();
        $provider     = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);
        $provider->method('provide')->willReturn($boundService);

        $injection = (new MethodCall(StubMethodCallBase::class, 'apply'))
            ->bindArgument('service')
            ->toProvider($provider);

        $container = $this->createStub(Container::class);
        $instance  = new StubMethodCallSubclass();

        $result = $injection->enhance($instance, $container);

        static::assertSame($instance, $result);
        static::assertSame($boundService, $instance->service);
        static::assertSame('default', $instance->suffix);
    }

    #[DataProvider('provideEqualMethodCalls')]
    public function testEqualsReturnsTrueForEqualMethodCalls(MethodCall $first, MethodCall $second): void
    {
        static::assertTrue($first->equals($second));
    }

    #[DataProvider('provideEqualMethodCalls')]
    public function testHashIsSameForEqualMethodCalls(MethodCall $first, MethodCall $second): void
    {
        static::assertSame($first->hash(), $second->hash());
    }

    #[DataProvider('provideDifferentObjects')]
    public function testEqualsReturnsFalseForDifferentObjects(MethodCall $first, object $second): void
    {
        static::assertFalse($first->equals($second));
    }

    /** @return array<string, array{MethodCall, MethodCall}> */
    public static function provideEqualMethodCalls(): array
    {
        $reflexive = new MethodCall(StubClass::class, 'setService');

        $provider = self::createStub(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);
        $provider->method('provide')->willReturn(new StubService());

        $withoutBinding = new MethodCall(StubClass::class, 'setService');
        $withBinding    = $withoutBinding->bindArgument('service')->toProvider($provider);

        return [
            'same instance' => [$reflexive, $reflexive],
            'same class and method' => [
                new MethodCall(StubClass::class, 'setService'),
                new MethodCall(StubClass::class, 'setService'),
            ],
            'argument bindings excluded from identity' => [$withoutBinding, $withBinding],
        ];
    }

    /** @return array<string, array{MethodCall, object}> */
    public static function provideDifferentObjects(): array
    {
        return [
            'same class, different method' => [
                new MethodCall(StubClass::class, 'setService'),
                new MethodCall(StubClass::class, 'withService'),
            ],
            'different class, same method' => [
                new MethodCall(StubMethodCallBase::class, 'apply'),
                new MethodCall(StubMethodCallSubclass::class, 'apply'),
            ],
            'different class' => [
                new MethodCall(StubClass::class, 'setService'),
                new stdClass(),
            ],
        ];
    }
}
