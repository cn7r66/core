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
use Vivarium\Container\Injection\MethodCall;
use Vivarium\Container\Provider;
use Vivarium\Test\Container\Stub\Stub;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubMethodCallBase;
use Vivarium\Test\Container\Stub\StubMethodCallSubclass;
use Vivarium\Test\Container\Stub\StubService;
use Vivarium\Test\Container\Stub\StubWithPrivateMethodInjection;

/** @coversDefaultClass \Vivarium\Container\Injection\MethodCall */
final class MethodCallTest extends TestCase
{
    /** @covers ::__construct */
    public function testThrowsForNonExistentMethod(): void
    {
        static::expectException(AssertionFailed::class);

        new MethodCall(StubClass::class, 'nonExistent');
    }

    /** @covers ::__construct */
    public function testThrowsForPrivateMethod(): void
    {
        static::expectException(AssertionFailed::class);

        new MethodCall(StubWithPrivateMethodInjection::class, 'setService');
    }

    /**
     * @covers ::__construct
     * @covers ::enhance
     */
    public function testEnhanceCallsMethodAndReturnsSameInstance(): void
    {
        $service   = new StubService();
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($service);

        $instance  = new StubClass(new StubService());
        $injection = new MethodCall(StubClass::class, 'setService');
        $result    = $injection->enhance($instance, $container);

        static::assertSame($instance, $result);
        static::assertSame($service, $instance->getService());
    }

    /**
     * @covers ::__construct
     * @covers ::enhance
     */
    public function testEnhanceFailsWhenInstanceTypeIsWrong(): void
    {
        static::expectException(AssertionFailed::class);

        $container = $this->createMock(Container::class);
        $injection = new MethodCall(StubClass::class, 'setService');
        $injection->enhance(new StubService(), $container);
    }

    /**
     * @covers ::__construct
     * @covers ::accept
     */
    public function testAcceptReturnsTrueWhenTargetHasMethod(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn(StubClass::class);

        static::assertTrue(
            (new MethodCall(StubClass::class, 'setService'))->accept($provider),
        );
    }

    /**
     * @covers ::__construct
     * @covers ::accept
     */
    public function testAcceptReturnsFalseWhenTargetLacksMethod(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);

        static::assertFalse(
            (new MethodCall(StubClass::class, 'setService'))->accept($provider),
        );
    }

    /**
     * @covers ::__construct
     * @covers ::accept
     */
    public function testAcceptReturnsFalseWhenTargetIsNotAClass(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('string');

        static::assertFalse(
            (new MethodCall(StubClass::class, 'setService'))->accept($provider),
        );
    }

    /**
     * @covers ::__construct
     * @covers ::getSlot
     */
    public function testGetSlotReturnsMethodName(): void
    {
        $injection = new MethodCall(StubClass::class, 'setService');

        static::assertSame('setService', $injection->getSlot());
    }

    /** @covers ::__construct */
    public function testConstructorException(): void
    {
        static::expectException(AssertionFailed::class);

        new MethodCall(Stub::class, 'setService');
    }

    /** @covers ::bindArgument */
    public function testBindArgument(): void
    {
        $boundService = new StubService();
        $provider     = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);
        $provider->method('provide')->willReturn($boundService);

        $original = new MethodCall(StubClass::class, 'setService');
        $bound    = $original->bindArgument('service')->toProvider($provider);

        static::assertNotSame($original, $bound);

        $resolvedService = new StubService();
        $container       = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolvedService);

        $boundInstance = new StubClass(new StubService());
        $bound->enhance($boundInstance, $container);
        static::assertSame($boundService, $boundInstance->getService());

        $originalInstance = new StubClass(new StubService());
        $original->enhance($originalInstance, $container);
        static::assertSame($resolvedService, $originalInstance->getService());
    }

    /** @covers ::bindArgumentAtPosition */
    public function testBindArgumentAtPosition(): void
    {
        $boundService = new StubService();
        $provider     = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);
        $provider->method('provide')->willReturn($boundService);

        $original = new MethodCall(StubClass::class, 'setService');
        $bound    = $original->bindArgumentAtPosition(0)->toProvider($provider);

        static::assertNotSame($original, $bound);

        $container = $this->createMock(Container::class);
        $instance  = new StubClass(new StubService());

        $bound->enhance($instance, $container);

        static::assertSame($boundService, $instance->getService());
    }

    /**
     * @covers ::enhance
     * @covers ::resolveArguments
     */
    public function testEnhanceProjection(): void
    {
        $boundService = new StubService();
        $provider     = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);
        $provider->method('provide')->willReturn($boundService);

        $injection = (new MethodCall(StubMethodCallBase::class, 'apply'))
            ->bindArgument('service')
            ->toProvider($provider);

        $container = $this->createMock(Container::class);
        $instance  = new StubMethodCallSubclass();

        $result = $injection->enhance($instance, $container);

        static::assertSame($instance, $result);
        static::assertSame($boundService, $instance->service);
        static::assertSame('default', $instance->suffix);
    }

    /**
     * @dataProvider provideEqualsCases
     * @covers ::equals
     */
    public function testEquals(MethodCall $a, object $b, bool $expected): void
    {
        static::assertSame($expected, $a->equals($b));
    }

    /**
     * @dataProvider provideEqualMethodCallPairs
     * @covers ::hash
     */
    public function testHash(MethodCall $a, MethodCall $b): void
    {
        static::assertSame($a->hash(), $b->hash());
    }

    /** @return iterable<string, array{0: MethodCall, 1: object, 2: bool}> */
    public static function provideEqualsCases(): iterable
    {
        $reflexive = new MethodCall(StubClass::class, 'setService');

        yield 'same instance' => [$reflexive, $reflexive, true];

        yield 'same class and method' => [
            new MethodCall(StubClass::class, 'setService'),
            new MethodCall(StubClass::class, 'setService'),
            true,
        ];

        yield 'same class, different method' => [
            new MethodCall(StubClass::class, 'setService'),
            new MethodCall(StubClass::class, 'withService'),
            false,
        ];

        yield 'different class, same method' => [
            new MethodCall(StubMethodCallBase::class, 'apply'),
            new MethodCall(StubMethodCallSubclass::class, 'apply'),
            false,
        ];

        yield 'different class' => [
            new MethodCall(StubClass::class, 'setService'),
            new stdClass(),
            false,
        ];

        $provider = self::createStub(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);
        $provider->method('provide')->willReturn(new StubService());

        $withoutBinding = new MethodCall(StubClass::class, 'setService');
        $withBinding    = $withoutBinding->bindArgument('service')->toProvider($provider);

        yield 'argument bindings excluded from identity' => [$withoutBinding, $withBinding, true];
    }

    /** @return iterable<string, array{0: MethodCall, 1: MethodCall}> */
    public static function provideEqualMethodCallPairs(): iterable
    {
        foreach (self::provideEqualsCases() as $key => $case) {
            if ($case[2] !== true) {
                continue;
            }

            if (! $case[1] instanceof MethodCall) {
                continue;
            }

            yield $key => [$case[0], $case[1]];
        }
    }
}
