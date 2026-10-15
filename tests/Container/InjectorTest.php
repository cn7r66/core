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
use Vivarium\Container\Provider\Constructor;
use Vivarium\Container\Registry;
use Vivarium\Container\Registry\EagerRegistry;
use Vivarium\Test\Container\Stub\StubAlterationBase;
use Vivarium\Test\Container\Stub\StubAlterationChild;
use Vivarium\Test\Container\Stub\StubBandDecorator;
use Vivarium\Test\Container\Stub\StubBandSubject;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubContractHook;
use Vivarium\Test\Container\Stub\StubContractHookImplementation;
use Vivarium\Test\Container\Stub\StubDecoratedBase;
use Vivarium\Test\Container\Stub\StubDecoratedDependency;
use Vivarium\Test\Container\Stub\StubDecoratedOuter;
use Vivarium\Test\Container\Stub\StubDecoratedSubject;
use Vivarium\Test\Container\Stub\StubDecoratedWrapper;
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
     * @covers ::applyAlterations
     * @covers ::applyChannel
     * @covers ::locate
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
     * @covers ::locate
     * @covers ::getProvider
     * @covers ::makeBinding
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
     * @covers ::locate
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
     * @covers ::locate
     * @covers ::getProvider
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
     * @covers ::locate
     * @covers ::getProvider
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

    /**
     * @covers ::has
     * @covers ::makeBinding
     */
    public function testHasReturnsFalseForUnknownInterface(): void
    {
        $container = new Injector(new EagerRegistry());

        static::assertFalse($container->has(Registry::class));
    }

    /**
     * @covers ::get
     * @covers ::applyAlterations
     * @covers ::applyChannel
     * @covers ::locate
     * @covers ::getProvider
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

    /**
     * @covers ::get
     * @covers ::locate
     * @covers ::getProvider
     * @dataProvider provideMethodInjectionRegistries
     */
    public function testMethodInjectionIsAppliedOnGet(Registry $registry): void
    {
        $container = new Injector($registry);

        $result = $container->get(StubClass::class);

        static::assertInstanceOf(StubClass::class, $result);
        static::assertInstanceOf(StubService::class, $result->service);
    }

    /** @return array<string, array{Registry}> */
    public static function provideMethodInjectionRegistries(): array
    {
        return [
            'mutable method' => [
                (new EagerRegistry())
                    ->inject(StubClass::class)->onMethod('setService')->withDefaultPriority(),
            ],
            'immutable method' => [
                (new EagerRegistry())
                    ->inject(StubClass::class)->onImmutableMethod('withService')->withDefaultPriority(),
            ],
        ];
    }

    /**
     * @covers ::get
     * @covers ::locate
     * @covers ::getProvider
     */
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
     * @covers ::locate
     * @covers ::getProvider
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

    /**
     * @covers ::get
     * @covers ::locate
     * @covers ::resolve
     * @covers ::withCache
     */
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

    /**
     * @covers ::has
     * @covers ::makeBinding
     */
    public function testHasReturnsTrueWhenAlreadySolved(): void
    {
        $container = new Injector(new EagerRegistry());
        $container->get(StubWithNoArgs::class);

        static::assertTrue($container->has(StubWithNoArgs::class));
    }

    /**
     * @covers ::has
     * @covers ::makeBinding
     */
    public function testHasReturnsTrueOnCacheHit(): void
    {
        $cache = $this->createMock(Cache::class);
        $cache->method('lookup')->willReturn(true);

        $container = (new Injector(new EagerRegistry()))->withCache($cache);

        static::assertTrue($container->has(StubWithNoArgs::class));
    }

    /**
     * @covers ::has
     * @covers ::makeBinding
     */
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
     * @covers ::locate
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
     * @covers ::applyAlterations
     * @covers ::applyChannel
     * @covers ::locate
     * @covers ::getProvider
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
     * @covers ::applyAlterations
     * @covers ::applyChannel
     */
    public function testServiceIsSharedAcrossDependentsInDifferentClasses(): void
    {
        $registry = (new EagerRegistry())->define(StubInjectorShared::class)->service();

        $container = new Injector($registry);

        $userB = $container->get(StubInjectorSharedUserB::class);
        $userC = $container->get(StubInjectorSharedUserC::class);

        static::assertSame($userB->shared, $userC->shared);
        static::assertSame($userB->shared, $container->get(StubInjectorShared::class));
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::locate
     * @covers ::resolve
     * @covers ::getProvider
     * @covers ::applyScope
     * @covers ::applyAlterations
     * @covers ::applyChannel
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
     * @covers ::applyAlterations
     * @covers ::applyChannel
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
     * @covers ::applyAlterations
     * @covers ::applyChannel
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
     * @covers ::getProvider
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
     * @covers ::makeBinding
     * @dataProvider provideRegistriesWithoutOtherTagRegistration
     */
    public function testHasIsFalseForTaggedClassWithoutRegistration(Registry $registry): void
    {
        $container = new Injector($registry);

        static::assertFalse($container->has(new Binding(StubWithNoArgs::class, 'other')));
    }

    /** @return array<string, array{Registry}> */
    public static function provideRegistriesWithoutOtherTagRegistration(): array
    {
        return [
            'empty registry' => [new EagerRegistry()],
            'default tag registered' => [(new EagerRegistry())->define(StubWithNoArgs::class)->transient()],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::resolve
     * @covers ::applyAlterations
     * @covers ::applyChannel
     * @covers ::locate
     * @covers ::getProvider
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
     * @covers ::locate
     * @covers ::getProvider
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
     * @covers ::locate
     * @covers ::getProvider
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

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::resolve
     * @covers ::applyAlterations
     * @covers ::applyChannel
     * @covers ::locate
     * @covers ::getProvider
     */
    public function testDefaultOrderIsInjectionThenCallThenDecoration(): void
    {
        $registry = (new EagerRegistry())
            ->define(StubBandSubject::class)->transient()
            ->decorate(StubBandSubject::class)->withDecorator(new StubBandDecorator())->withDefaultPriority()
            ->call(StubBandSubject::class)->onMethod('markCall')->withDefaultPriority()
            ->inject(StubBandSubject::class)->onMethod('markInjection')->withDefaultPriority();

        $result = (new Injector($registry))->get(StubBandSubject::class);

        static::assertInstanceOf(StubBandSubject::class, $result);
        static::assertSame(['injection', 'call', 'decoration'], $result->log);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::resolve
     * @covers ::applyAlterations
     * @covers ::applyChannel
     * @covers ::locate
     * @covers ::getProvider
     * @dataProvider provideInterfaceInjectionRegistries
     */
    public function testInjectionRegisteredOnAnInterfaceAppliesToItsImplementation(
        Registry $registry,
        string $requested,
    ): void {
        $container = new Injector($registry);

        $result = $container->get($requested);

        static::assertInstanceOf(StubContractHookImplementation::class, $result);
        static::assertSame(1, $result->calls);
    }

    /** @return array<string, array{Registry, string}> */
    public static function provideInterfaceInjectionRegistries(): array
    {
        return [
            'implementation resolved directly' => [
                (new EagerRegistry())
                    ->inject(StubContractHook::class)->onMethod('m')->withDefaultPriority(),
                StubContractHookImplementation::class,
            ],
            'implementation resolved through the interface binding' => [
                (new EagerRegistry())
                    ->bind(StubContractHook::class)->to(StubContractHookImplementation::class)->transient()
                    ->inject(StubContractHook::class)->onMethod('m')->withDefaultPriority(),
                StubContractHook::class,
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::resolve
     * @covers ::applyAlterations
     * @covers ::applyChannel
     * @covers ::locate
     * @covers ::getProvider
     * @dataProvider provideWrappingDecoratorRegistries
     */
    public function testDecoratorWrapsResolvedObjectAndAutowiresOtherParameters(Registry $registry): void
    {
        $result = (new Injector($registry))->get(StubDecoratedSubject::class);

        static::assertInstanceOf(StubDecoratedWrapper::class, $result);
        static::assertInstanceOf(StubDecoratedBase::class, $result->inner);
        static::assertInstanceOf(StubDecoratedDependency::class, $result->dependency);
    }

    /** @return array<string, array{Registry}> */
    public static function provideWrappingDecoratorRegistries(): array
    {
        $function = static function (
            StubDecoratedSubject $inner,
            StubDecoratedDependency $dependency,
        ): StubDecoratedSubject {
            return new StubDecoratedWrapper($inner, $dependency);
        };

        return [
            'constructor decorator' => [
                (new EagerRegistry())
                    ->bind(StubDecoratedSubject::class)->to(StubDecoratedBase::class)->transient()
                    ->decorate(StubDecoratedSubject::class)->with(StubDecoratedWrapper::class, 'inner')
                        ->withDefaultPriority(),
            ],
            'function decorator' => [
                (new EagerRegistry())
                    ->bind(StubDecoratedSubject::class)->to(StubDecoratedBase::class)->transient()
                    ->decorate(StubDecoratedSubject::class)->withFunction($function, 'inner')->withDefaultPriority(),
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::resolve
     * @covers ::applyAlterations
     * @covers ::applyChannel
     * @covers ::locate
     * @covers ::getProvider
     */
    public function testDecoratorConfigureCallbackBindsAnotherConstructorArgument(): void
    {
        $registry = (new EagerRegistry())
            ->bind(StubDecoratedSubject::class)->to(StubDecoratedBase::class)->transient()
            ->decorate(StubDecoratedSubject::class)->with(
                StubDecoratedWrapper::class,
                'inner',
                static fn (Constructor $c) => $c->bindArgument('level')->toInstance(3),
            )->withDefaultPriority();

        $result = (new Injector($registry))->get(StubDecoratedSubject::class);

        static::assertInstanceOf(StubDecoratedWrapper::class, $result);
        static::assertInstanceOf(StubDecoratedBase::class, $result->inner);
        static::assertSame(3, $result->level);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::resolve
     * @covers ::applyAlterations
     * @covers ::applyChannel
     * @covers ::locate
     * @covers ::getProvider
     */
    public function testLaterRegisteredDecoratorIsOutermostWithDefaultPriority(): void
    {
        $registry = (new EagerRegistry())
            ->bind(StubDecoratedSubject::class)->to(StubDecoratedBase::class)->transient()
            ->decorate(StubDecoratedSubject::class)->with(StubDecoratedWrapper::class, 'inner')->withDefaultPriority()
            ->decorate(StubDecoratedSubject::class)->with(StubDecoratedOuter::class, 'inner')->withDefaultPriority();

        $result = (new Injector($registry))->get(StubDecoratedSubject::class);

        static::assertInstanceOf(StubDecoratedOuter::class, $result);
        static::assertInstanceOf(StubDecoratedWrapper::class, $result->inner);
        static::assertInstanceOf(StubDecoratedBase::class, $result->inner->inner);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::resolve
     * @covers ::applyAlterations
     * @covers ::applyChannel
     * @covers ::locate
     * @covers ::getProvider
     */
    public function testComposedDecoratorFeedsCallableResultToNextFunction(): void
    {
        $registry = (new EagerRegistry())
            ->bind('callable')->toInstance(static fn (int $a, int $b): int => $a + $b)->service()
            ->decorate('callable')->compose(static fn (int $n): int => $n * 10, 'n')->withDefaultPriority();

        $result = (new Injector($registry))->get('callable');

        static::assertIsCallable($result);
        static::assertSame(50, $result(2, 3));
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::resolve
     * @covers ::applyAlterations
     * @covers ::applyChannel
     * @covers ::locate
     * @covers ::getProvider
     */
    public function testServiceScopedBindingIsDecoratedOnce(): void
    {
        $registry = (new EagerRegistry())
            ->bind(StubDecoratedSubject::class)->to(StubDecoratedBase::class)->service()
            ->decorate(StubDecoratedSubject::class)->with(StubDecoratedWrapper::class, 'inner')->withDefaultPriority();

        $container = new Injector($registry);
        $first     = $container->get(StubDecoratedSubject::class);

        static::assertInstanceOf(StubDecoratedWrapper::class, $first);
        static::assertSame($first, $container->get(StubDecoratedSubject::class));
    }
}
