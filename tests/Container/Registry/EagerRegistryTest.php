<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Registry;

use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Comparator\Priority;
use Vivarium\Comparator\ValueAndPriority;
use Vivarium\Container\Binding;
use Vivarium\Container\Binding\DecoratorBinder;
use Vivarium\Container\Binding\InjectionBinder;
use Vivarium\Container\Binding\ProviderBinder;
use Vivarium\Container\Binding\ScopeBinder;
use Vivarium\Container\Decorator;
use Vivarium\Container\Exception\AmbiguousInjection;
use Vivarium\Container\Exception\BindingNotFound;
use Vivarium\Container\Exception\DuplicateBinding;
use Vivarium\Container\Exception\DuplicateInjection;
use Vivarium\Container\Injection;
use Vivarium\Container\Injection\MethodCall;
use Vivarium\Container\Provider;
use Vivarium\Container\Registry\EagerRegistry;
use Vivarium\Container\Scope;
use Vivarium\Test\Container\Stub\Stub;
use Vivarium\Test\Container\Stub\StubAmbiguousFirst;
use Vivarium\Test\Container\Stub\StubAmbiguousImplementation;
use Vivarium\Test\Container\Stub\StubAmbiguousSecond;
use Vivarium\Test\Container\Stub\StubBase;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubDuplicateSlot;
use Vivarium\Test\Container\Stub\StubWithNoArgs;

use function array_map;
use function sort;

/** @coversDefaultClass \Vivarium\Container\Registry\EagerRegistry */
final class EagerRegistryTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::hasProvider
     * @covers ::findScope
     * @covers ::findInjections
     * @covers ::findCalls
     * @covers ::findDecorations
     */
    public function testNewRegistryHasNothingRegistered(): void
    {
        $registry = new EagerRegistry();
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        static::assertFalse($registry->hasProvider($binding));
        static::assertSame(Scope::TRANSIENT, $registry->findScope($binding));
        static::assertEmpty($this->toArray($registry->findInjections($binding)));
        static::assertEmpty($this->toArray($registry->findCalls($binding)));
        static::assertEmpty($this->toArray($registry->findDecorations($binding)));
    }

    /**
     * @covers ::bind
     * @covers ::scopedRegistration
     */
    public function testBindReturnsProviderBinder(): void
    {
        $registry = new EagerRegistry();

        $binder = $registry->bind(StubClass::class);

        static::assertInstanceOf(ProviderBinder::class, $binder);
    }

    /**
     * @covers ::bind
     * @covers ::scopedRegistration
     */
    public function testBindingProviderReturnsScopeBinder(): void
    {
        $binder = (new EagerRegistry())
            ->bind(StubClass::class)
            ->toProvider($this->createMock(Provider::class));

        static::assertInstanceOf(ScopeBinder::class, $binder);
    }

    /**
     * @covers ::bind
     * @covers ::scopedRegistration
     */
    public function testAfterBindingProviderHasProviderReturnsTrue(): void
    {
        $provider = $this->createMock(Provider::class);
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->toProvider($provider)->transient();

        static::assertTrue($registry->hasProvider($binding));
    }

    /**
     * @covers ::bind
     * @covers ::scopedRegistration
     */
    public function testBindIsImmutableOriginalHasNoProvider(): void
    {
        $provider = $this->createMock(Provider::class);
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $original = new EagerRegistry();
        $original->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)->toProvider($provider)->transient();

        static::assertFalse($original->hasProvider($binding));
    }

    /**
     * @covers ::bind
     * @covers ::scopedRegistration
     */
    public function testBindWithTagCreatesDistinctBinding(): void
    {
        $provider       = $this->createMock(Provider::class);
        $bindingTagged  = new Binding(StubClass::class, 'custom', Binding::GLOBAL);
        $bindingDefault = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->bind(StubClass::class, 'custom', Binding::GLOBAL)
            ->toProvider($provider)->transient();

        static::assertTrue($registry->hasProvider($bindingTagged));
        static::assertFalse($registry->hasProvider($bindingDefault));
    }

    /**
     * @covers ::bind
     * @covers ::__construct
     * @covers ::scopedRegistration
     */
    public function testBindingTheSameBindingTwiceIsRejected(): void
    {
        $binding  = new Binding(StubClass::class, 'audit', 'App\\Orders');
        $registry = (new EagerRegistry())
            ->bind(StubClass::class, 'audit', 'App\\Orders')
            ->toProvider($this->createMock(Provider::class))
            ->transient();

        try {
            $registry->bind(StubClass::class, 'audit', 'App\\Orders');
            static::fail('Expected DuplicateBinding');
        } catch (DuplicateBinding $exception) {
            static::assertTrue($binding->equals($exception->getBinding()));
        }
    }

    /** @covers ::define */
    public function testDefineReturnsScopeBinder(): void
    {
        $binder = (new EagerRegistry())->define(StubWithNoArgs::class);

        static::assertInstanceOf(ScopeBinder::class, $binder);
    }

    /** @covers ::define */
    public function testDefineRegistersProviderForNoArgConstructor(): void
    {
        $binding  = new Binding(StubWithNoArgs::class, Binding::DEFAULT, Binding::GLOBAL);
        $registry = (new EagerRegistry())->define(StubWithNoArgs::class)->transient();

        static::assertTrue($registry->hasProvider($binding));
    }

    /** @covers ::define */
    public function testDefineIsImmutableOriginalHasNoProvider(): void
    {
        $binding  = new Binding(StubWithNoArgs::class, Binding::DEFAULT, Binding::GLOBAL);
        $original = new EagerRegistry();
        $original->define(StubWithNoArgs::class)->transient();

        static::assertFalse($original->hasProvider($binding));
    }

    /**
     * @covers ::define
     * @covers ::bind
     * @covers ::__construct
     * @covers ::scopedRegistration
     */
    public function testDefiningTheSameClassTwiceIsRejected(): void
    {
        $binding  = new Binding(StubWithNoArgs::class, Binding::DEFAULT, Binding::GLOBAL);
        $registry = (new EagerRegistry())->define(StubWithNoArgs::class)->transient();

        try {
            $registry->define(StubWithNoArgs::class);
            static::fail('Expected DuplicateBinding');
        } catch (DuplicateBinding $exception) {
            static::assertTrue($binding->equals($exception->getBinding()));
        }
    }

    /**
     * @covers ::extend
     * @covers ::scopedRegistration
     */
    public function testExtendReturnsProviderBinder(): void
    {
        $provider = $this->createMock(Provider::class);
        $registry = (new EagerRegistry())
            ->bind(StubClass::class)
            ->toProvider($provider)->transient();

        $binder = $registry->extend(StubClass::class);

        static::assertInstanceOf(ProviderBinder::class, $binder);
    }

    /**
     * @covers ::extend
     * @covers ::bind
     * @covers ::__construct
     * @covers ::scopedRegistration
     * @dataProvider provideTagsAndContexts
     */
    public function testExtendReplacesExistingProvider(string $tag, string $context): void
    {
        $first   = $this->createMock(Provider::class);
        $second  = $this->createMock(Provider::class);
        $binding = new Binding(StubClass::class, $tag, $context);

        $registry = (new EagerRegistry())
            ->bind(StubClass::class, $tag, $context)
            ->toProvider($first)->transient()
            ->extend(StubClass::class, $tag, $context)
            ->toProvider($second)->transient();

        static::assertSame($second, $registry->findProvider($binding));
    }

    /** @return array<string, array{string, string}> */
    public static function provideTagsAndContexts(): array
    {
        return [
            'default tag, global context' => [Binding::DEFAULT, Binding::GLOBAL],
            'custom tag, namespace context' => ['audit', 'App\\Orders'],
        ];
    }

    /**
     * @covers ::extend
     * @covers ::scopedRegistration
     */
    public function testExtendThrowsWhenBindingNotFound(): void
    {
        $this->expectException(AssertionFailed::class);

        (new EagerRegistry())->extend(StubClass::class);
    }

    /**
     * @covers ::bind
     * @covers ::scopedRegistration
     * @covers ::findScope
     */
    public function testServiceScopeIsRegistered(): void
    {
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);
        $registry = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->toProvider($this->createMock(Provider::class))
            ->service();

        static::assertSame(Scope::SERVICE, $registry->findScope($binding));
    }

    /**
     * @covers ::bind
     * @covers ::scopedRegistration
     * @covers ::findScope
     */
    public function testCloneableScopeIsRegistered(): void
    {
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);
        $registry = (new EagerRegistry())
            ->bind(StubClass::class)
            ->toProvider($this->createMock(Provider::class))
            ->cloneable();

        static::assertSame(Scope::CLONEABLE, $registry->findScope($binding));
    }

    /**
     * @covers ::bind
     * @covers ::scopedRegistration
     */
    public function testRegisteringWithScopeIsImmutableOriginalReturnsTransient(): void
    {
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);
        $original = new EagerRegistry();
        $original->bind(StubClass::class)->toProvider($this->createMock(Provider::class))->service();

        static::assertSame(Scope::TRANSIENT, $original->findScope($binding));
    }

    /**
     * @covers ::findScope
     * @dataProvider provideSupertypesOfStubClass
     */
    public function testFindScopeDoesNotWalkHierarchyToSupertype(string $supertype): void
    {
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);
        $registry = (new EagerRegistry())
            ->bind($supertype)
            ->toProvider($this->createMock(Provider::class))
            ->service();

        static::assertSame(Scope::TRANSIENT, $registry->findScope($binding));
    }

    /** @return array<string, array{string}> */
    public static function provideSupertypesOfStubClass(): array
    {
        return [
            'parent class' => [StubBase::class],
            'interface' => [Stub::class],
        ];
    }

    /**
     * @covers ::hasProvider
     * @dataProvider provideRequestsAgainstGlobalDefaultRegistration
     */
    public function testHasProviderMatchesTypeAndTagExactlyAndWidensOnlyContext(
        Binding $requested,
        bool $expected,
    ): void {
        $registry = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->toProvider($this->createMock(Provider::class))->transient();

        static::assertSame($expected, $registry->hasProvider($requested));
    }

    /** @return array<string, array{Binding, bool}> */
    public static function provideRequestsAgainstGlobalDefaultRegistration(): array
    {
        return [
            'exact binding' => [new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL), true],
            'parent type of the registered class' => [
                new Binding(StubBase::class, Binding::DEFAULT, Binding::GLOBAL),
                false,
            ],
            'custom tag' => [new Binding(StubClass::class, 'custom', Binding::GLOBAL), false],
            'namespace context widens to global' => [
                new Binding(StubClass::class, Binding::DEFAULT, 'Vivarium\Test\Container'),
                true,
            ],
            'custom tag in namespace context' => [
                new Binding(StubClass::class, 'custom', 'Vivarium\Test\Container'),
                false,
            ],
        ];
    }

    /**
     * @param list<string> $registered
     *
     * @covers ::locate
     * @covers ::hasProvider
     * @covers ::registeredBinding
     * @covers ::__construct
     * @dataProvider provideLocatableContexts
     */
    public function testLocateReturnsRegisteredBindingWithMostSpecificMatchingContext(
        string $tag,
        array $registered,
        string $requested,
        string $expected,
    ): void {
        $registry = new EagerRegistry();

        foreach ($registered as $context) {
            $registry = $registry
                ->bind(StubClass::class, $tag, $context)
                ->toProvider($this->createMock(Provider::class))
                ->transient();
        }

        $request = new Binding(StubClass::class, $tag, $requested);
        $located = $registry->locate($request);

        static::assertTrue($registry->hasProvider($request));
        static::assertTrue((new Binding(StubClass::class, $tag, $expected))->equals($located));
    }

    /** @return array<string, array{string, list<string>, string, string}> */
    public static function provideLocatableContexts(): array
    {
        return [
            'exact global' => [Binding::DEFAULT, [Binding::GLOBAL], Binding::GLOBAL, Binding::GLOBAL],
            'exact namespace' => [Binding::DEFAULT, ['App\\Orders'], 'App\\Orders', 'App\\Orders'],
            'specific request widens to global' => [
                Binding::DEFAULT,
                [Binding::GLOBAL],
                'App\\Orders\\Checkout',
                Binding::GLOBAL,
            ],
            'specific request widens to namespace' => [
                Binding::DEFAULT,
                ['App\\Orders'],
                'App\\Orders\\Checkout',
                'App\\Orders',
            ],
            'most specific registration wins' => [
                Binding::DEFAULT,
                [Binding::GLOBAL, 'App\\Orders', 'App\\Orders\\Checkout'],
                'App\\Orders\\Checkout',
                'App\\Orders\\Checkout',
            ],
            'nearest ancestor wins over global' => [
                Binding::DEFAULT,
                [Binding::GLOBAL, 'App\\Orders'],
                'App\\Orders\\Checkout',
                'App\\Orders',
            ],
            'tagged request widens keeping its tag' => ['audit', [Binding::GLOBAL], 'App\\Orders', Binding::GLOBAL],
        ];
    }

    /**
     * @param list<string> $registered
     *
     * @covers ::locate
     * @covers ::registeredBinding
     * @covers ::__construct
     * @dataProvider provideUnlocatableRequests
     */
    public function testLocateThrowsBindingNotFoundCarryingRequestedBinding(
        array $registered,
        string $requestedContext,
    ): void {
        $registry = new EagerRegistry();

        foreach ($registered as $context) {
            $registry = $registry
                ->bind(StubClass::class, Binding::DEFAULT, $context)
                ->toProvider($this->createMock(Provider::class))
                ->transient();
        }

        $requested = new Binding(StubWithNoArgs::class, Binding::DEFAULT, $requestedContext);

        try {
            $registry->locate($requested);
            static::fail('Expected BindingNotFound');
        } catch (BindingNotFound $exception) {
            static::assertTrue($requested->equals($exception->getBinding()));
        }
    }

    /** @return array<string, array{list<string>, string}> */
    public static function provideUnlocatableRequests(): array
    {
        return [
            'empty registry' => [[], 'App\\Orders\\Checkout'],
            'different type, same context' => [[Binding::GLOBAL], Binding::GLOBAL],
            'different type, wider registration' => [[Binding::GLOBAL], 'App\\Orders\\Checkout'],
        ];
    }

    /**
     * @covers ::findProvider
     * @dataProvider provideRequestsLocatingGlobalDefaultRegistration
     */
    public function testFindProviderReturnsRegisteredProvider(Binding $requested): void
    {
        $provider = $this->createMock(Provider::class);

        $registry = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->toProvider($provider)->transient();

        static::assertSame($provider, $registry->findProvider($requested));
    }

    /** @return array<string, array{Binding}> */
    public static function provideRequestsLocatingGlobalDefaultRegistration(): array
    {
        return [
            'exact binding' => [new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)],
            'namespace context widens to global' => [
                new Binding(StubClass::class, Binding::DEFAULT, 'Vivarium\Test\Container'),
            ],
        ];
    }

    /** @covers ::findProvider */
    public function testFindProviderPrefersMoreSpecificBinding(): void
    {
        $parentProvider = $this->createMock(Provider::class);
        $childProvider  = $this->createMock(Provider::class);
        $binding        = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->bind(StubBase::class)
            ->toProvider($parentProvider)->transient()
            ->bind(StubClass::class)
            ->toProvider($childProvider)->transient();

        static::assertSame($childProvider, $registry->findProvider($binding));
    }

    /** @covers ::findProvider */
    public function testFindProviderPrefersMoreSpecificContextOverGlobal(): void
    {
        $globalProvider   = $this->createMock(Provider::class);
        $specificProvider = $this->createMock(Provider::class);
        $binding          = new Binding(StubClass::class, Binding::DEFAULT, 'Vivarium\Test\Container');

        $registry = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->toProvider($globalProvider)->transient()
            ->bind(StubClass::class, Binding::DEFAULT, 'Vivarium\Test\Container')
            ->toProvider($specificProvider)->transient();

        static::assertSame($specificProvider, $registry->findProvider($binding));
    }

    /** @covers ::findProvider */
    public function testFindProviderThrowsWhenTaggedRequestHasOnlyDefaultTagRegistration(): void
    {
        $provider = $this->createMock(Provider::class);
        $binding  = new Binding(StubClass::class, 'custom', Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->toProvider($provider)->transient();

        $this->expectException(BindingNotFound::class);

        $registry->findProvider($binding);
    }

    /** @covers ::findProvider */
    public function testFindProviderThrowsWhenNoProviderFound(): void
    {
        $this->expectException(BindingNotFound::class);

        $registry = new EagerRegistry();
        $registry->findProvider(new Binding(StubClass::class));
    }

    /** @covers ::inject */
    public function testInjectReturnsInjectionBinder(): void
    {
        $binder = (new EagerRegistry())->inject(StubClass::class);

        static::assertInstanceOf(InjectionBinder::class, $binder);
    }

    /** @covers ::inject */
    public function testInjectIsImmutableOriginalHasNoInjections(): void
    {
        $injection = $this->mockInjection('slot');
        $binding   = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $original = new EagerRegistry();
        $original->inject(StubClass::class)->withInjection($injection)->withDefaultPriority();

        static::assertEmpty($this->toArray($original->findInjections($binding)));
    }

    /**
     * @covers ::inject
     * @covers ::__construct
     */
    public function testInjectingTheSameSlotTwiceOnOneBindingIsRejected(): void
    {
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);
        $registry = (new EagerRegistry())
            ->inject(StubClass::class)
            ->onProperty('property')
            ->withDefaultPriority();

        try {
            $registry->inject(StubClass::class)->onProperty('property')->withDefaultPriority();
            static::fail('Expected DuplicateInjection');
        } catch (DuplicateInjection $exception) {
            static::assertTrue($binding->equals($exception->getBinding()));
            static::assertSame('$property', $exception->getSlot());
        }
    }

    /**
     * @covers ::inject
     * @covers ::findInjections
     * @covers ::__construct
     */
    public function testPropertyAndMethodWithTheSameNameAreDifferentSlots(): void
    {
        $registry = (new EagerRegistry())
            ->inject(StubDuplicateSlot::class)
            ->onProperty('logger')
            ->withDefaultPriority()
            ->inject(StubDuplicateSlot::class)
            ->onMethod('logger')
            ->withDefaultPriority();

        $binding = new Binding(StubDuplicateSlot::class, Binding::DEFAULT, Binding::GLOBAL);

        static::assertCount(2, $this->toArray($registry->findInjections($binding)));
    }

    /** @covers ::call */
    public function testCallReturnsInjectionBinder(): void
    {
        $binder = (new EagerRegistry())->call(StubClass::class);

        static::assertInstanceOf(InjectionBinder::class, $binder);
    }

    /** @covers ::call */
    public function testCallIsImmutableOriginalHasNoCalls(): void
    {
        $injection = $this->mockInjection('slot');
        $binding   = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $original = new EagerRegistry();
        $original->call(StubClass::class)->withInjection($injection)->withDefaultPriority();

        static::assertEmpty($this->toArray($original->findCalls($binding)));
    }

    /**
     * @covers ::call
     * @covers ::findCalls
     * @covers ::__construct
     */
    public function testCallingTheSameMethodTwiceIsAllowed(): void
    {
        $registry = (new EagerRegistry())
            ->call(StubClass::class)
            ->onMethod('setService')
            ->withDefaultPriority()
            ->call(StubClass::class)
            ->onMethod('setService')
            ->withDefaultPriority();

        $binding = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        static::assertCount(2, $this->toArray($registry->findCalls($binding)));
    }

    /** @covers ::decorate */
    public function testDecorateReturnsDecoratorBinder(): void
    {
        $binder = (new EagerRegistry())->decorate(StubClass::class);

        static::assertInstanceOf(DecoratorBinder::class, $binder);
    }

    /** @covers ::decorate */
    public function testDecorateIsImmutableOriginalHasNoDecorations(): void
    {
        $decorator = $this->createMock(Decorator::class);
        $binding   = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $original = new EagerRegistry();
        $original->decorate(StubClass::class)->withDecorator($decorator)->withDefaultPriority();

        static::assertEmpty($this->toArray($original->findDecorations($binding)));
    }

    /**
     * @covers ::decorate
     * @covers ::findDecorations
     * @covers ::__construct
     */
    public function testSameDecoratorRegisteredTwiceIsReturnedTwice(): void
    {
        $decorator = $this->createMock(Decorator::class);

        $registry = (new EagerRegistry())
            ->decorate(StubClass::class)->withDecorator($decorator)->withDefaultPriority()
            ->decorate(StubClass::class)->withDecorator($decorator)->withDefaultPriority();

        $result = $this->toArray($registry->findDecorations(new Binding(StubClass::class)));

        static::assertCount(2, $result);
        static::assertSame($decorator, $result[0]->getValue());
        static::assertSame($decorator, $result[1]->getValue());
    }

    /**
     * @covers ::call
     * @covers ::decorate
     * @covers ::findInjections
     * @covers ::findCalls
     * @covers ::findDecorations
     * @covers ::__construct
     */
    public function testCallsAndDecorationsAreReturnedByTheirOwnChannelOnly(): void
    {
        $injection = $this->mockInjection('slot');
        $decorator = $this->createMock(Decorator::class);

        $registry = (new EagerRegistry())
            ->call(StubClass::class)->withInjection($injection)->withDefaultPriority()
            ->decorate(StubClass::class)->withDecorator($decorator)->withDefaultPriority();

        $binding     = new Binding(StubClass::class);
        $calls       = $this->toArray($registry->findCalls($binding));
        $decorations = $this->toArray($registry->findDecorations($binding));

        static::assertCount(1, $calls);
        static::assertSame($injection, $calls[0]->getValue());
        static::assertCount(1, $decorations);
        static::assertSame($decorator, $decorations[0]->getValue());
        static::assertEmpty($this->toArray($registry->findInjections($binding)));
    }

    /**
     * @dataProvider provideChannelDefaultPriorities
     * @covers ::inject
     * @covers ::call
     * @covers ::decorate
     * @covers ::findInjections
     * @covers ::findCalls
     * @covers ::findDecorations
     * @covers ::__construct
     */
    public function testDefaultPriorityDependsOnChannel(string $channel, int $expected): void
    {
        $binding = new Binding(StubClass::class);

        $registry = (new EagerRegistry())
            ->inject(StubClass::class)->withInjection($this->mockInjection('slot'))->withDefaultPriority()
            ->call(StubClass::class)->withInjection($this->mockInjection('slot'))->withDefaultPriority()
            ->decorate(StubClass::class)->withDecorator($this->createMock(Decorator::class))->withDefaultPriority();

        $found = [
            'injection' => $registry->findInjections($binding),
            'call' => $registry->findCalls($binding),
            'decoration' => $registry->findDecorations($binding),
        ];

        $result = $this->toArray($found[$channel]);

        static::assertCount(1, $result);
        static::assertSame($expected, $result[0]->getPriority());
    }

    /** @return array<string, array{string, int}> */
    public static function provideChannelDefaultPriorities(): array
    {
        return [
            'injection' => ['injection', Priority::LOW],
            'call' => ['call', Priority::NORMAL],
            'decoration' => ['decoration', Priority::HIGH],
        ];
    }

    /**
     * @covers ::call
     * @covers ::findCalls
     * @covers ::__construct
     */
    public function testFindCallsReturnsSortedAscendingByPriority(): void
    {
        $lowPriority  = $this->mockInjection('slot');
        $highPriority = $this->mockInjection('slot2');

        $registry = (new EagerRegistry())
            ->call(StubClass::class)->withInjection($highPriority)->withPriority(10)
            ->call(StubClass::class)->withInjection($lowPriority)->withPriority(1);

        $result = $this->toArray($registry->findCalls(new Binding(StubClass::class)));

        static::assertCount(2, $result);
        static::assertSame(1, $result[0]->getPriority());
        static::assertSame(10, $result[1]->getPriority());
    }

    /**
     * @covers ::decorate
     * @covers ::findDecorations
     * @covers ::__construct
     */
    public function testDecorationsWithEqualPriorityAreAllKept(): void
    {
        $first  = $this->createMock(Decorator::class);
        $second = $this->createMock(Decorator::class);
        $third  = $this->createMock(Decorator::class);

        $registry = (new EagerRegistry())
            ->decorate(StubClass::class)->withDecorator($first)->withPriority(5)
            ->decorate(StubClass::class)->withDecorator($second)->withPriority(5)
            ->decorate(StubClass::class)->withDecorator($third)->withPriority(5);

        $result = $this->toArray($registry->findDecorations(new Binding(StubClass::class)));

        static::assertCount(3, $result);
        static::assertSame($first, $result[0]->getValue());
        static::assertSame($second, $result[1]->getValue());
        static::assertSame($third, $result[2]->getValue());
    }

    /**
     * @covers ::__construct
     * @covers ::inject
     * @covers ::findInjections
     * @dataProvider provideInjectionsReachingStubClass
     */
    public function testFindInjectionsContainsInjectionRegisteredOnTheTypeOrASupertype(
        string $injectedType,
        Binding $requested,
    ): void {
        $injection = $this->mockInjection('slot');

        $registry = (new EagerRegistry())
            ->inject($injectedType)
            ->withInjection($injection)
            ->withDefaultPriority();

        $enhancements = $this->toArray($registry->findInjections($requested));

        static::assertCount(1, $enhancements);
        static::assertSame($injection, $enhancements[0]->getValue());
    }

    /** @return array<string, array{string, Binding}> */
    public static function provideInjectionsReachingStubClass(): array
    {
        return [
            'injected on the same type' => [
                StubClass::class,
                new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL),
            ],
            'injected on a parent type' => [
                StubBase::class,
                new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL),
            ],
            'untagged injection applies to tagged binding' => [
                StubClass::class,
                new Binding(StubClass::class, 'audit', Binding::GLOBAL),
            ],
        ];
    }

    /**
     * @covers ::inject
     * @covers ::findInjections
     * @covers ::__construct
     */
    public function testInjectWithDifferentSlotsCollectsBothFromHierarchy(): void
    {
        $parentInjection = $this->mockInjection('slotA');
        $childInjection  = $this->mockInjection('slotB');

        $registry = (new EagerRegistry())
            ->inject(StubBase::class)->withInjection($parentInjection)->withDefaultPriority()
            ->inject(StubClass::class)->withInjection($childInjection)->withDefaultPriority();

        $result = $this->toArray($registry->findInjections(new Binding(StubClass::class)));

        $values = array_map(static function (ValueAndPriority $entry): mixed {
            return $entry->getValue();
        }, $result);

        static::assertContains($parentInjection, $values);
        static::assertContains($childInjection, $values);
    }

    /**
     * @param list<array{class-string, string, string}> $registrations
     * @param list<string>                              $expected
     *
     * @covers ::inject
     * @covers ::findInjections
     * @covers ::__construct
     * @dataProvider provideEqualPriorityInjectionsAcrossTheHierarchy
     */
    public function testEqualPriorityInjectionsApplyLeastSpecificFirst(array $registrations, array $expected): void
    {
        $registry   = new EagerRegistry();
        $injections = [];

        foreach ($registrations as [$type, $context, $slot]) {
            $injections[$slot] = $this->mockInjection($slot);
            $registry          = $registry->inject($type, $context)
                ->withInjection($injections[$slot])
                ->withDefaultPriority();
        }

        $result = $this->toArray(
            $registry->findInjections(new Binding(StubClass::class, Binding::DEFAULT, 'App\\Orders')),
        );

        $applied = array_map(static function (ValueAndPriority $entry): mixed {
            return $entry->getValue();
        }, $result);

        static::assertSame(
            array_map(static fn (string $slot): Injection => $injections[$slot], $expected),
            $applied,
        );
    }

    /** @return array<string, array{list<array{class-string, string, string}>, list<string>}> */
    public static function provideEqualPriorityInjectionsAcrossTheHierarchy(): array
    {
        return [
            'supertype before subtype' => [
                [[StubClass::class, Binding::GLOBAL, 'child'], [StubBase::class, Binding::GLOBAL, 'parent']],
                ['parent', 'child'],
            ],
            'wider context before narrower context' => [
                [[StubClass::class, 'App\\Orders', 'narrow'], [StubClass::class, Binding::GLOBAL, 'wide']],
                ['wide', 'narrow'],
            ],
            'registration order within one binding' => [
                [[StubClass::class, Binding::GLOBAL, 'first'], [StubClass::class, Binding::GLOBAL, 'second']],
                ['first', 'second'],
            ],
        ];
    }

    /**
     * @param list<array{class-string, string}> $registrations
     * @param class-string                      $requestedType
     *
     * @covers ::inject
     * @covers ::findInjections
     * @covers ::__construct
     * @dataProvider provideCompetingRegistrationsForOneSlot
     */
    public function testMostSpecificRegistrationWinsTheSlot(
        array $registrations,
        string $requestedType,
        string $requestedContext,
        int $winner,
    ): void {
        $registry   = new EagerRegistry();
        $injections = [];

        foreach ($registrations as [$type, $context]) {
            $injection    = $this->mockInjection('slot');
            $injections[] = $injection;
            $registry     = $registry->inject($type, $context)->withInjection($injection)->withDefaultPriority();
        }

        $result = $this->toArray(
            $registry->findInjections(new Binding($requestedType, Binding::DEFAULT, $requestedContext)),
        );

        static::assertCount(1, $result);
        static::assertSame($injections[$winner], $result[0]->getValue());
    }

    /** @return array<string, array{list<array{class-string, string}>, class-string, string, int}> */
    public static function provideCompetingRegistrationsForOneSlot(): array
    {
        return [
            'subtype shadows supertype' => [
                [[StubBase::class, Binding::GLOBAL], [StubClass::class, Binding::GLOBAL]],
                StubClass::class,
                Binding::GLOBAL,
                1,
            ],
            'subtype wins over supertype registered at narrower context' => [
                [[StubBase::class, 'App\\Orders'], [StubClass::class, Binding::GLOBAL]],
                StubClass::class,
                'App\\Orders',
                1,
            ],
            'narrower context wins for same type' => [
                [[StubClass::class, Binding::GLOBAL], [StubClass::class, 'App\\Orders']],
                StubClass::class,
                'App\\Orders',
                1,
            ],
            'registration on common subtype resolves ambiguity' => [
                [
                    [StubAmbiguousFirst::class, Binding::GLOBAL],
                    [StubAmbiguousSecond::class, Binding::GLOBAL],
                    [StubAmbiguousImplementation::class, Binding::GLOBAL],
                ],
                StubAmbiguousImplementation::class,
                Binding::GLOBAL,
                2,
            ],
        ];
    }

    /**
     * @covers ::inject
     * @covers ::findInjections
     * @covers ::__construct
     */
    public function testUnrelatedSupertypesRegisteringSameSlotAreAmbiguous(): void
    {
        $registry = (new EagerRegistry())
            ->inject(StubAmbiguousFirst::class)
            ->withInjection(new MethodCall(StubAmbiguousFirst::class, 'configure'))
            ->withDefaultPriority()
            ->inject(StubAmbiguousSecond::class)
            ->withInjection(new MethodCall(StubAmbiguousSecond::class, 'configure'))
            ->withDefaultPriority();

        try {
            $this->toArray($registry->findInjections(new Binding(StubAmbiguousImplementation::class)));
            static::fail('Expected AmbiguousInjection');
        } catch (AmbiguousInjection $e) {
            $types = array_map(static function (Binding $binding): string {
                return $binding->getType();
            }, $e->getCandidates());
            sort($types);

            static::assertSame('configure', $e->getSlot());
            static::assertSame([StubAmbiguousFirst::class, StubAmbiguousSecond::class], $types);
        }
    }

    /**
     * @covers ::call
     * @covers ::findCalls
     * @covers ::__construct
     */
    public function testCallDoesNotCollectFromParentClassBinding(): void
    {
        $injection = $this->mockInjection('slot');

        $registry = (new EagerRegistry())
            ->call(StubBase::class)
            ->withInjection($injection)
            ->withDefaultPriority();

        $result = $this->toArray($registry->findCalls(new Binding(StubClass::class)));

        static::assertEmpty($result);
    }

    /**
     * @covers ::call
     * @covers ::findCalls
     * @covers ::__construct
     */
    public function testReturnsEmptyCallsForBindingWhoseChildHoldsTheCall(): void
    {
        $injection = $this->mockInjection('slot');

        $registry = (new EagerRegistry())
            ->call(StubClass::class)
            ->withInjection($injection)
            ->withDefaultPriority();

        $result = $this->toArray($registry->findCalls(new Binding(StubBase::class)));

        static::assertEmpty($result);
    }

    /**
     * @covers ::decorate
     * @covers ::findDecorations
     * @covers ::__construct
     */
    public function testDecorationDoesNotCollectFromParentClassBinding(): void
    {
        $decorator = $this->createMock(Decorator::class);

        $registry = (new EagerRegistry())
            ->decorate(StubBase::class)
            ->withDecorator($decorator)
            ->withDefaultPriority();

        $result = $this->toArray($registry->findDecorations(new Binding(StubClass::class)));

        static::assertEmpty($result);
    }

    private function mockInjection(string $slot): Injection
    {
        $injection = $this->createMock(Injection::class);
        $injection->method('getSlot')->willReturn($slot);

        return $injection;
    }

    /**
     * @param iterable<ValueAndPriority<mixed>> $items
     *
     * @return array<ValueAndPriority<mixed>>
     */
    private function toArray(iterable $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = $item;
        }

        return $result;
    }
}
