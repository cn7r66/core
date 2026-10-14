<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Provider;

use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Provider;
use Vivarium\Container\Provider\Constructor;
use Vivarium\Test\Container\Stub\NotInstantiableStub;
use Vivarium\Test\Container\Stub\Stub;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubService;
use Vivarium\Test\Container\Stub\StubWithMultipleArgs;
use Vivarium\Test\Container\Stub\StubWithNoArgs;
use Vivarium\Test\Container\Stub\StubWithNoConstructor;
use Vivarium\Test\Container\Stub\StubWithOptionalArg;
use Vivarium\Test\Container\Stub\StubWithUntypedOptionalArg;
use Vivarium\Test\Container\Stub\StubWithUntypedRequiredArg;
use Vivarium\Test\Container\Stub\StubWithVariadicArgs;

/** @coversDefaultClass \Vivarium\Container\Provider\Constructor */
final class ConstructorTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::provide
     * @covers ::resolveArguments
     */
    public function testProvideCreatesInstanceWithDependency(): void
    {
        $service   = new StubService();
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($service);

        $result = (new Constructor(StubClass::class))->provide($container);

        static::assertInstanceOf(StubClass::class, $result);
        static::assertSame($service, $result->service);
    }

    /**
     * @covers ::__construct
     * @covers ::provide
     * @covers ::resolveArguments
     */
    public function testProvideCreatesInstanceWithNoArgs(): void
    {
        $container = $this->createMock(Container::class);
        $container->expects(static::never())->method('get');

        $result = (new Constructor(StubWithNoArgs::class))->provide($container);

        static::assertInstanceOf(StubWithNoArgs::class, $result);
    }

    /**
     * @covers ::__construct
     * @covers ::provide
     * @covers ::resolveArguments
     */
    public function testProvideUsesDefaultWhenOptionalArgNotInContainer(): void
    {
        $container = $this->createMock(Container::class);
        $container->method('has')->willReturn(false);

        $result = (new Constructor(StubWithOptionalArg::class))->provide($container);

        static::assertInstanceOf(StubWithOptionalArg::class, $result);
        static::assertSame('default', $result->name);
    }

    /**
     * @covers ::__construct
     * @covers ::provide
     * @covers ::resolveArguments
     */
    public function testProvideUsesContainerValueForOptionalArg(): void
    {
        $container = $this->createMock(Container::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn('custom');

        $result = (new Constructor(StubWithOptionalArg::class))->provide($container);

        static::assertSame('custom', $result->name);
    }

    /**
     * @covers ::__construct
     * @covers ::provide
     * @covers ::resolveArguments
     */
    public function testProvideWithUntypedOptionalArgUsesDefault(): void
    {
        $container = $this->createMock(Container::class);
        $container->expects(static::never())->method('get');

        $result = (new Constructor(StubWithUntypedOptionalArg::class))->provide($container);

        static::assertSame('default', $result->getValue());
    }

    /**
     * @covers ::__construct
     * @covers ::provide
     * @covers ::resolveArguments
     */
    public function testProvideThrowsForUntypedRequiredArg(): void
    {
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn('resolved');

        $result = (new Constructor(StubWithUntypedRequiredArg::class))->provide($container);

        static::assertSame('resolved', $result->getValue());
    }

    /**
     * @covers ::__construct
     * @covers ::bindArgument
     * @covers ::provide
     * @covers ::resolveArguments
     */
    public function testProvidePrefersExplicitlyBoundArgumentOverAutoResolution(): void
    {
        $service = new StubService();
        $custom  = $this->createMock(Provider::class);
        $custom->method('getTarget')->willReturn(StubService::class);
        $custom->method('provide')->willReturn($service);

        $container = $this->createMock(Container::class);
        $container->expects(static::never())->method('get');

        $result = (new Constructor(StubClass::class))
            ->bindArgument('service')
            ->toProvider($custom)
            ->provide($container);

        static::assertInstanceOf(StubClass::class, $result);
        static::assertSame($service, $result->service);
    }

    /** @covers ::getTarget */
    public function testGetTargetReturnsClassName(): void
    {
        static::assertSame(
            StubClass::class,
            (new Constructor(StubClass::class))->getTarget(),
        );
    }

    /** @covers ::getCapabilities */
    public function testGetCapabilitiesIncludesAll(): void
    {
        $capabilities = (new Constructor(StubClass::class))->getCapabilities();

        static::assertTrue($capabilities->contains(Capability::INJECTABLE));
        static::assertTrue($capabilities->contains(Capability::INTERCEPTABLE));
        static::assertTrue($capabilities->contains(Capability::DECORABLE));
        static::assertCount(3, $capabilities);
    }

    /**
     * @covers ::__construct
     * @covers ::provide
     * @covers ::resolveArguments
     */
    public function testProvideCreatesInstanceWithNoExplicitConstructor(): void
    {
        $container = $this->createMock(Container::class);
        $container->expects(static::never())->method('get');

        $result = (new Constructor(StubWithNoConstructor::class))->provide($container);

        static::assertInstanceOf(StubWithNoConstructor::class, $result);
    }

    /** @covers ::__construct */
    public function testThrowsForNotInstantiableClass(): void
    {
        static::expectException(AssertionFailed::class);

        new Constructor(NotInstantiableStub::class);
    }

    /** @covers ::bindArgument */
    public function testBindArgumentThrowsForUnknownParameterName(): void
    {
        static::expectException(AssertionFailed::class);

        (new Constructor(StubClass::class))->bindArgument('nonExistent');
    }

    /** @covers ::bindArgument */
    public function testBindArgumentThrowsForImplicitConstructor(): void
    {
        static::expectException(AssertionFailed::class);

        (new Constructor(StubWithNoConstructor::class))->bindArgument('value');
    }

    /** @covers ::bindArgumentAtPosition */
    public function testBindArgumentAtPositionThrowsForOutOfBoundsPosition(): void
    {
        static::expectException(AssertionFailed::class);

        (new Constructor(StubWithMultipleArgs::class))->bindArgumentAtPosition(3);
    }

    /** @covers ::bindArgumentAtPosition */
    public function testBindArgumentAtPositionThrowsForNegativePosition(): void
    {
        static::expectException(AssertionFailed::class);

        (new Constructor(StubWithMultipleArgs::class))->bindArgumentAtPosition(-1);
    }

    /** @covers ::equals */
    public function testEqualsReturnsTrueForSameClassAndMethod(): void
    {
        $a = new Constructor(StubClass::class);
        $b = new Constructor(StubClass::class);

        static::assertTrue($a->equals($b));
        static::assertTrue($a->equals($a));
    }

    /** @covers ::equals */
    public function testEqualsReturnsFalseForDifferentClass(): void
    {
        $a = new Constructor(StubClass::class);
        $b = new Constructor(StubWithNoArgs::class);

        static::assertFalse($a->equals($b));
    }

    /** @covers ::equals */
    public function testEqualsReturnsFalseForNonMethod(): void
    {
        static::assertFalse(
            (new Constructor(StubClass::class))->equals(new stdClass()),
        );
    }

    /** @covers ::hash */
    public function testHashIsConsistentForSameInstance(): void
    {
        $a = new Constructor(StubClass::class);

        static::assertSame($a->hash(), $a->hash());
    }

    /** @covers ::hash */
    public function testHashDiffersForDifferentClasses(): void
    {
        $a = new Constructor(StubClass::class);
        $b = new Constructor(StubWithNoArgs::class);

        static::assertNotSame($a->hash(), $b->hash());
    }

    /** @covers ::__construct */
    public function testConstructor(): void
    {
        $constructor = new Constructor(StubWithNoConstructor::class);

        static::assertSame(StubWithNoConstructor::class, $constructor->getTarget());
    }

    /** @covers ::__construct */
    public function testConstructorException(): void
    {
        static::expectException(AssertionFailed::class);

        new Constructor(Stub::class);
    }

    /** @covers ::bindArgument */
    public function testBindArgument(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('string');
        $provider->method('provide')->willReturn('bound');

        $original = new Constructor(StubWithOptionalArg::class);
        $bound    = $original->bindArgument('name')->toProvider($provider);

        static::assertNotSame($original, $bound);

        $container = $this->createMock(Container::class);
        $container->method('has')->willReturn(false);

        static::assertSame('bound', $bound->provide($container)->name);
        static::assertSame('default', $original->provide($container)->name);
    }

    /** @covers ::bindArgument */
    public function testBindArgumentException(): void
    {
        static::expectException(AssertionFailed::class);

        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('string');

        (new Constructor(StubClass::class))->bindArgument('service')->toProvider($provider);
    }

    /** @covers ::bindArgumentAtPosition */
    public function testBindArgumentAtPosition(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('array');
        $provider->method('provide')->willReturn(['a', 'b', 'c']);

        $constructor = (new Constructor(StubWithVariadicArgs::class))
            ->bindArgumentAtPosition(0)
            ->toProvider($provider);

        $container = $this->createMock(Container::class);

        $result = $constructor->provide($container);

        static::assertSame(['a', 'b', 'c'], $result->names);
    }

    /** @covers ::bindArgumentAtPosition */
    public function testBindArgumentAtPositionException(): void
    {
        static::expectException(AssertionFailed::class);

        (new Constructor(StubWithNoConstructor::class))->bindArgumentAtPosition(0);
    }

    /** @covers ::provide */
    public function testProvide(): void
    {
        $container = $this->createMock(Container::class);

        $result = (new Constructor(StubWithVariadicArgs::class))->provide($container);

        static::assertSame([], $result->names);
    }

    /** @covers ::getTarget */
    public function testGetTarget(): void
    {
        static::assertSame(
            StubWithMultipleArgs::class,
            (new Constructor(StubWithMultipleArgs::class))->getTarget(),
        );
    }

    /** @covers ::getCapabilities */
    public function testGetCapabilities(): void
    {
        $capabilities = (new Constructor(StubWithNoConstructor::class))->getCapabilities();

        static::assertTrue($capabilities->contains(Capability::INJECTABLE));
        static::assertTrue($capabilities->contains(Capability::INTERCEPTABLE));
        static::assertTrue($capabilities->contains(Capability::DECORABLE));
        static::assertCount(3, $capabilities);
    }

    /** @covers ::equals */
    public function testEquals(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);

        $a = new Constructor(StubClass::class);
        $b = (new Constructor(StubClass::class))->bindArgument('service')->toProvider($provider);

        static::assertTrue($a->equals($b));
    }

    /** @covers ::hash */
    public function testHash(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);

        $a = new Constructor(StubClass::class);
        $b = (new Constructor(StubClass::class))->bindArgument('service')->toProvider($provider);

        static::assertSame($a->hash(), $b->hash());
    }
}
