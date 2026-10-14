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
use Vivarium\Container\Exception\CircularDependency;
use Vivarium\Container\Exception\ResolutionFailed;
use Vivarium\Container\Injection;
use Vivarium\Container\Injector;
use Vivarium\Container\Registry;
use Vivarium\Container\Registry\EagerRegistry;
use Vivarium\Test\Container\Stub\StubAlterationBase;
use Vivarium\Test\Container\Stub\StubAlterationChild;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubInjectorContract;
use Vivarium\Test\Container\Stub\StubInjectorCycleA;
use Vivarium\Test\Container\Stub\StubInjectorCycleB;
use Vivarium\Test\Container\Stub\StubInjectorCycleC;
use Vivarium\Test\Container\Stub\StubInjectorDiamondTop;
use Vivarium\Test\Container\Stub\StubInjectorNeedsInterface;
use Vivarium\Test\Container\Stub\StubInjectorShared;
use Vivarium\Test\Container\Stub\StubInjectorSharedUserB;
use Vivarium\Test\Container\Stub\StubInjectorSharedUserC;
use Vivarium\Test\Container\Stub\StubService;
use Vivarium\Test\Container\Stub\StubWithNoArgs;

use function array_map;

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

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::locate
     * @covers ::resolve
     * @covers ::getProvider
     * @covers ::applyScope
     * @covers ::applyEnhancements
     */
    public function testServiceIsSharedAcrossDependentsInDifferentClasses(): void
    {
        $registry = (new EagerRegistry())->define(StubInjectorShared::class)->service();

        $container = new Injector($registry);

        $b = $container->get(StubInjectorSharedUserB::class);
        $c = $container->get(StubInjectorSharedUserC::class);

        static::assertSame($b->d, $c->d);
        static::assertSame($b->d, $container->get(StubInjectorShared::class));
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::locate
     * @covers ::resolve
     * @covers ::getProvider
     * @covers ::applyScope
     * @covers ::applyEnhancements
     */
    public function testCircularDependencyIsDetectedOnFirstRepeat(): void
    {
        $container = new Injector(new EagerRegistry());

        try {
            $container->get(StubInjectorCycleA::class);
        } catch (CircularDependency $failure) {
            $types = array_map(static fn (Binding $binding): string => $binding->getType(), $failure->getPath());

            static::assertSame(
                [
                    StubInjectorCycleA::class,
                    StubInjectorCycleB::class,
                    StubInjectorCycleC::class,
                    StubInjectorCycleA::class,
                ],
                $types,
            );
            static::assertSame(
                'Circular dependency: [' . StubInjectorCycleA::class . ' → ' . StubInjectorCycleB::class
                . ' → ' . StubInjectorCycleC::class . ' → ' . StubInjectorCycleA::class . ']',
                $failure->getMessage(),
            );

            return;
        }

        static::fail('Expected CircularDependency to be thrown.');
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::locate
     * @covers ::resolve
     * @covers ::getProvider
     * @covers ::applyScope
     * @covers ::applyEnhancements
     */
    public function testDiamondDependencyIsNotACycle(): void
    {
        $container = new Injector(new EagerRegistry());

        static::assertInstanceOf(StubInjectorDiamondTop::class, $container->get(StubInjectorDiamondTop::class));
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::locate
     * @covers ::resolve
     * @covers ::getProvider
     * @covers ::applyScope
     * @covers ::applyEnhancements
     */
    public function testMissingDependencyFailureCarriesResolutionPath(): void
    {
        $container = new Injector(new EagerRegistry());

        try {
            $container->get(StubInjectorNeedsInterface::class);
        } catch (ResolutionFailed $failure) {
            $types = array_map(static fn (Binding $binding): string => $binding->getType(), $failure->getPath());

            static::assertSame([StubInjectorNeedsInterface::class, StubInjectorContract::class], $types);
            static::assertInstanceOf(BindingNotFound::class, $failure->getPrevious());

            return;
        }

        static::fail('Expected ResolutionFailed to be thrown.');
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::locate
     * @covers ::resolve
     * @dataProvider provideRegistriesWithoutOtherTagRegistration
     */
    public function testTaggedClassWithoutRegistrationIsNotAutowired(Registry $registry): void
    {
        $container = new Injector($registry);

        try {
            $container->get(new Binding(StubWithNoArgs::class, 'other'));
        } catch (ResolutionFailed $failure) {
            static::assertInstanceOf(BindingNotFound::class, $failure->getPrevious());

            return;
        }

        static::fail('Expected ResolutionFailed to be thrown.');
    }

    /**
     * @covers ::__construct
     * @covers ::has
     * @dataProvider provideRegistriesWithoutOtherTagRegistration
     */
    public function testHasIsFalseForTaggedClassWithoutRegistration(Registry $registry): void
    {
        $container = new Injector($registry);

        static::assertFalse($container->has(new Binding(StubWithNoArgs::class, 'other')));
    }

    /** @return iterable<string, array{Registry}> */
    public static function provideRegistriesWithoutOtherTagRegistration(): iterable
    {
        yield 'empty registry' => [new EagerRegistry()];

        yield 'default tag registered' => [(new EagerRegistry())->define(StubWithNoArgs::class)->transient()];
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::resolve
     * @covers ::applyAlterations
     * @covers ::applyChannel
     */
    public function testInjectionThroughBindingChainIsAppliedOnce(): void
    {
        $registry  = (new EagerRegistry())
            ->bind(StubAlterationBase::class)->to(StubAlterationChild::class)->transient()
            ->inject(StubAlterationBase::class)->onMethod('increment')->withDefaultPriority();
        $container = new Injector($registry);

        $result = $container->get(StubAlterationBase::class);

        static::assertInstanceOf(StubAlterationChild::class, $result);
        static::assertSame(1, $result->calls);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::resolve
     * @covers ::applyAlterations
     * @covers ::applyChannel
     */
    public function testCallOnAliasBindingIsAppliedOnce(): void
    {
        $registry  = (new EagerRegistry())
            ->bind(StubAlterationBase::class)->to(StubAlterationChild::class)->transient()
            ->call(StubAlterationBase::class)->onMethod('increment')->withDefaultPriority();
        $container = new Injector($registry);

        $viaAlias = $container->get(StubAlterationBase::class);
        $direct   = $container->get(StubAlterationChild::class);

        static::assertInstanceOf(StubAlterationBase::class, $viaAlias);
        static::assertSame(1, $viaAlias->calls);
        static::assertInstanceOf(StubAlterationChild::class, $direct);
        static::assertSame(0, $direct->calls);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::resolve
     * @covers ::applyAlterations
     * @covers ::applyChannel
     */
    public function testInjectionIsNotAppliedToSuppliedInstance(): void
    {
        $instance  = new StubAlterationBase();
        $registry  = (new EagerRegistry())
            ->bind(StubAlterationBase::class)->toInstance($instance)->transient()
            ->inject(StubAlterationBase::class)->onMethod('increment')->withDefaultPriority();
        $container = new Injector($registry);

        static::assertSame($instance, $container->get(StubAlterationBase::class));
        static::assertSame(0, $instance->calls);
    }
}
