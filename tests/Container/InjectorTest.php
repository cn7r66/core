<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container;

use PHPUnit\Framework\TestCase;
use Vivarium\Container\Binding;
use Vivarium\Container\Cache;
use Vivarium\Container\Cache\NoOpCache;
use Vivarium\Container\Collector\NoOpCollector;
use Vivarium\Container\Definition;
use Vivarium\Container\Exception\BindingNotFound;
use Vivarium\Container\Exception\ResolutionFailed;
use Vivarium\Container\Injection;
use Vivarium\Container\Injector;
use Vivarium\Container\Registry;
use Vivarium\Container\Registry\EagerRegistry;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubService;
use Vivarium\Test\Container\Stub\StubWithNoArgs;

/** @coversDefaultClass \Vivarium\Container\Injector */
final class InjectorTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::has
     * @covers ::makeBinding
     * @covers ::getProvider
     * @covers ::applyScope
     * @covers ::applyEnhancements
     */
    public function testAutoWiresConcreteClassWithNoArgs(): void
    {
        $container = new Injector(new EagerRegistry());

        static::assertTrue($container->has(StubWithNoArgs::class));
        static::assertInstanceOf(StubWithNoArgs::class, $container->get(StubWithNoArgs::class));
    }

    /**
     * @covers ::get
     * @covers ::has
     */
    public function testAutoWiresConcreteClassWithDependency(): void
    {
        $container = new Injector(new EagerRegistry());

        $result = $container->get(StubClass::class);

        static::assertInstanceOf(StubClass::class, $result);
        static::assertInstanceOf(StubService::class, $result->service);
    }

    /**
     * @covers ::get
     * @covers ::getProvider
     */
    public function testExplicitBindingOverridesAutoWiring(): void
    {
        $instance = new StubWithNoArgs();

        $registry = (new EagerRegistry())
            ->bind(StubWithNoArgs::class)
            ->toInstance($instance)
            ->transient();

        $container = new Injector($registry);

        static::assertSame($instance, $container->get(StubWithNoArgs::class));
    }

    /**
     * @covers ::get
     * @covers ::applyScope
     */
    public function testServiceScopeReturnsSameInstance(): void
    {
        $registry = (new EagerRegistry())
            ->bind(StubWithNoArgs::class)->toConstructor()->service();

        $container = new Injector($registry);

        static::assertSame(
            $container->get(StubWithNoArgs::class),
            $container->get(StubWithNoArgs::class),
        );
    }

    /**
     * @covers ::get
     * @covers ::applyScope
     */
    public function testTransientScopeReturnsDifferentInstances(): void
    {
        $registry = (new EagerRegistry())
            ->bind(StubWithNoArgs::class)->toConstructor()->transient();

        $container = new Injector($registry);

        static::assertNotSame(
            $container->get(StubWithNoArgs::class),
            $container->get(StubWithNoArgs::class),
        );
    }

    /** @covers ::has */
    public function testHasReturnsFalseForUnknownInterface(): void
    {
        $container = new Injector(new EagerRegistry());

        static::assertFalse($container->has(Registry::class));
    }

    /**
     * @covers ::get
     * @covers ::applyEnhancements
     */
    public function testPropertyInjectionIsAppliedOnGet(): void
    {
        $registry = (new EagerRegistry())
            ->bind('string', Binding::DEFAULT, StubClass::class)
                ->toClassConstant(StubClass::class, 'STRING_CONSTANT')->transient()
            ->inject(StubClass::class)->onProperty('property')->withDefaultPriority();

        $container = new Injector($registry);

        $result = $container->get(StubClass::class);

        static::assertInstanceOf(StubClass::class, $result);
        static::assertSame(StubClass::STRING_CONSTANT, $result->property);
    }

    /** @covers ::get */
    public function testMethodInjectionIsAppliedOnGet(): void
    {
        $registry = (new EagerRegistry())
            ->inject(StubClass::class)->onMethod('setService')->withDefaultPriority();

        $container = new Injector($registry);

        $result = $container->get(StubClass::class);

        static::assertInstanceOf(StubClass::class, $result);
        static::assertInstanceOf(StubService::class, $result->service);
    }

    /** @covers ::get */
    public function testImmutableMethodInjectionIsAppliedOnGet(): void
    {
        $registry = (new EagerRegistry())
            ->inject(StubClass::class)->onImmutableMethod('withService')->withDefaultPriority();

        $container = new Injector($registry);

        $result = $container->get(StubClass::class);

        static::assertInstanceOf(StubClass::class, $result);
        static::assertInstanceOf(StubService::class, $result->service);
    }

    /** @covers ::get */
    public function testEnhancementsAppliedBeforeScopeCaching(): void
    {
        $registry = (new EagerRegistry())
            ->bind(StubClass::class)->toConstructor()->service()
            ->inject(StubClass::class)->onMethod('setService')->withDefaultPriority();

        $container = new Injector($registry);

        $first  = $container->get(StubClass::class);
        $second = $container->get(StubClass::class);

        static::assertSame($first, $second);
        static::assertInstanceOf(StubService::class, $first->service);
    }

    /**
     * @covers ::get
     * @covers ::makeBinding
     */
    public function testGetAcceptsBindingObject(): void
    {
        $container = new Injector(new EagerRegistry());
        $binding   = new Binding(StubWithNoArgs::class);

        static::assertInstanceOf(StubWithNoArgs::class, $container->get($binding));
    }

    /** @covers ::withCache */
    public function testWithCacheReturnsNewInjector(): void
    {
        $container = new Injector(new EagerRegistry());
        $withCache = $container->withCache(new NoOpCache());

        static::assertNotSame($container, $withCache);
        static::assertInstanceOf(StubWithNoArgs::class, $withCache->get(StubWithNoArgs::class));
    }

    /** @covers ::withCollector */
    public function testWithCollectorReturnsNewInjector(): void
    {
        $container     = new Injector(new EagerRegistry());
        $withCollector = $container->withCollector(new NoOpCollector());

        static::assertNotSame($container, $withCollector);
        static::assertInstanceOf(StubWithNoArgs::class, $withCollector->get(StubWithNoArgs::class));
    }

    /** @covers ::get */
    public function testGetReturnsRestoredDefinitionOnCacheHit(): void
    {
        $expected   = new StubWithNoArgs();
        $definition = $this->createMock(Definition::class);
        $definition->method('solve')->willReturn($expected);

        $cache = $this->createMock(Cache::class);
        $cache->method('lookup')->willReturn(true);
        $cache->method('restore')->willReturn($definition);

        $container = (new Injector(new EagerRegistry()))->withCache($cache);

        static::assertSame($expected, $container->get(StubWithNoArgs::class));
    }

    /** @covers ::has */
    public function testHasReturnsTrueWhenAlreadySolved(): void
    {
        $container = new Injector(new EagerRegistry());
        $container->get(StubWithNoArgs::class);

        static::assertTrue($container->has(StubWithNoArgs::class));
    }

    /** @covers ::has */
    public function testHasReturnsTrueOnCacheHit(): void
    {
        $cache = $this->createMock(Cache::class);
        $cache->method('lookup')->willReturn(true);

        $container = (new Injector(new EagerRegistry()))->withCache($cache);

        static::assertTrue($container->has(StubWithNoArgs::class));
    }

    /** @covers ::has */
    public function testHasReturnsTrueWhenRegistryHasProvider(): void
    {
        $registry = (new EagerRegistry())
            ->bind(StubWithNoArgs::class)->toConstructor()->transient();

        $container = new Injector($registry);

        static::assertTrue($container->has(StubWithNoArgs::class));
    }

    /**
     * @covers ::get
     * @covers ::getProvider
     */
    public function testGetThrowsWhenProviderNotFoundAndTypeIsNotAClass(): void
    {
        $container = new Injector(new EagerRegistry());

        try {
            $container->get('string');
        } catch (ResolutionFailed $failure) {
            static::assertInstanceOf(BindingNotFound::class, $failure->getPrevious());

            return;
        }

        static::fail('Expected ResolutionFailed to be thrown.');
    }

    /**
     * @covers ::get
     * @covers ::applyEnhancements
     */
    public function testGetSkipsEnhancementThatRejectsProvider(): void
    {
        $injection = $this->createMock(Injection::class);
        $injection->method('accept')->willReturn(false);
        $injection->method('getSlot')->willReturn('slot');

        $registry = (new EagerRegistry())
            ->inject(StubWithNoArgs::class)->withInjection($injection)->withDefaultPriority();

        $container = new Injector($registry);

        static::assertInstanceOf(StubWithNoArgs::class, $container->get(StubWithNoArgs::class));
    }
}
