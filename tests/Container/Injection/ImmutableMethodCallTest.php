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
    /** @covers ::__construct */
    public function testThrowsForNonExistentMethod(): void
    {
        static::expectException(AssertionFailed::class);

        new ImmutableMethodCall(StubClass::class, 'nonExistent');
    }

    /** @covers ::__construct */
    public function testThrowsForPrivateMethod(): void
    {
        static::expectException(AssertionFailed::class);

        new ImmutableMethodCall(StubWithPrivateMethodInjection::class, 'setService');
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
     */
    public function testAcceptReturnsTrueWhenTargetHasMethod(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn(StubClass::class);

        static::assertTrue(
            (new ImmutableMethodCall(StubClass::class, 'withService'))->accept($provider),
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
            (new ImmutableMethodCall(StubClass::class, 'withService'))->accept($provider),
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
            (new ImmutableMethodCall(StubClass::class, 'withService'))->accept($provider),
        );
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
     * @dataProvider provideEqualsCases
     * @covers ::__construct
     * @covers ::equals
     */
    public function testEquals(ImmutableMethodCall $a, object $b, bool $expected): void
    {
        static::assertSame($expected, $a->equals($b));
    }

    /**
     * @dataProvider provideEqualMethodCallPairs
     * @covers ::__construct
     * @covers ::hash
     */
    public function testHash(ImmutableMethodCall $a, ImmutableMethodCall $b): void
    {
        static::assertSame($a->hash(), $b->hash());
    }

    /** @return iterable<string, array{0: ImmutableMethodCall, 1: object, 2: bool}> */
    public static function provideEqualsCases(): iterable
    {
        $reflexive = new ImmutableMethodCall(StubClass::class, 'withService');

        yield 'same instance' => [$reflexive, $reflexive, true];

        yield 'same class and method' => [
            new ImmutableMethodCall(StubClass::class, 'withService'),
            new ImmutableMethodCall(StubClass::class, 'withService'),
            true,
        ];

        yield 'same class, different method' => [
            new ImmutableMethodCall(StubClass::class, 'withService'),
            new ImmutableMethodCall(StubClass::class, 'setService'),
            false,
        ];

        yield 'different class, same method name' => [
            new ImmutableMethodCall(StubClass::class, 'withService'),
            new ImmutableMethodCall(StubWithWrongReturn::class, 'withSomething'),
            false,
        ];

        yield 'not an ImmutableMethodCall' => [
            new ImmutableMethodCall(StubClass::class, 'withService'),
            new stdClass(),
            false,
        ];

        $provider = self::createStub(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);
        $provider->method('provide')->willReturn(new StubService());

        $withoutBinding = new ImmutableMethodCall(StubClass::class, 'withService');
        $withBinding    = $withoutBinding->bindArgument('service')->toProvider($provider);

        yield 'argument bindings excluded from identity' => [$withoutBinding, $withBinding, true];
    }

    /** @return iterable<string, array{0: ImmutableMethodCall, 1: ImmutableMethodCall}> */
    public static function provideEqualMethodCallPairs(): iterable
    {
        foreach (self::provideEqualsCases() as $key => $case) {
            if ($case[2] !== true) {
                continue;
            }

            if (! $case[1] instanceof ImmutableMethodCall) {
                continue;
            }

            yield $key => [$case[0], $case[1]];
        }
    }
}
