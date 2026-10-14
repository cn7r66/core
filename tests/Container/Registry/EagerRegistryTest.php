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
use Vivarium\Comparator\ValueAndPriority;
use Vivarium\Container\Binding;
use Vivarium\Container\Binding\DecoratorBinder;
use Vivarium\Container\Binding\InjectionBinder;
use Vivarium\Container\Binding\ProviderBinder;
use Vivarium\Container\Binding\ScopeBinder;
use Vivarium\Container\Decorator;
use Vivarium\Container\Exception\BindingNotFound;
use Vivarium\Container\Exception\DuplicateBinding;
use Vivarium\Container\Exception\DuplicateInjection;
use Vivarium\Container\Injection;
use Vivarium\Container\Provider;
use Vivarium\Container\Registry\EagerRegistry;
use Vivarium\Container\Scope;
use Vivarium\Test\Container\Stub\Stub;
use Vivarium\Test\Container\Stub\StubBase;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubDuplicateSlot;
use Vivarium\Test\Container\Stub\StubWithNoArgs;

/** @coversDefaultClass \Vivarium\Container\Registry\EagerRegistry */
final class EagerRegistryTest extends TestCase
{
    /** @covers ::bind */
    public function testBindReturnsProviderBinder(): void
    {
        $registry = new EagerRegistry();

        $binder = $registry->bind(StubClass::class);

        static::assertInstanceOf(ProviderBinder::class, $binder);
    }

    /** @covers ::bind */
    public function testAfterBindingProviderHasProviderReturnsTrue(): void
    {
        $provider = $this->createMock(Provider::class);
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->toProvider($provider)->transient();

        static::assertTrue($registry->hasProvider($binding));
    }

    /** @covers ::bind */
    public function testBindIsImmutableOriginalHasNoProvider(): void
    {
        $provider = $this->createMock(Provider::class);
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $original = new EagerRegistry();
        $original->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)->toProvider($provider)->transient();

        static::assertFalse($original->hasProvider($binding));
    }

    /** @covers ::bind */
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

    /** @covers ::extend */
    public function testExtendReturnsProviderBinder(): void
    {
        $provider = $this->createMock(Provider::class);
        $registry = (new EagerRegistry())
            ->bind(StubClass::class)
            ->toProvider($provider)->transient();

        $binder = $registry->extend(StubClass::class);

        static::assertInstanceOf(ProviderBinder::class, $binder);
    }

    /** @covers ::extend */
    public function testExtendReplacesExistingProvider(): void
    {
        $first   = $this->createMock(Provider::class);
        $second  = $this->createMock(Provider::class);
        $binding = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->bind(StubClass::class)
            ->toProvider($first)->transient()
            ->extend(StubClass::class)
            ->toProvider($second)->transient();

        static::assertSame($second, $registry->findProvider($binding));
    }

    /** @covers ::extend */
    public function testExtendThrowsWhenBindingNotFound(): void
    {
        $this->expectException(AssertionFailed::class);

        (new EagerRegistry())->extend(StubClass::class);
    }

    /** @covers ::define */
    public function testDefineReturnsScopeBinder(): void
    {
        $binder = (new EagerRegistry())->define(StubWithNoArgs::class);

        static::assertInstanceOf(ScopeBinder::class, $binder);
    }

    /** @covers ::bind */
    public function testBindingProviderReturnsScopeBinder(): void
    {
        $binder = (new EagerRegistry())
            ->bind(StubClass::class)
            ->toProvider($this->createMock(Provider::class));

        static::assertInstanceOf(ScopeBinder::class, $binder);
    }

    /** @covers ::bind */
    public function testAfterRegisteringWithScopeFindScopeReturnsChosenScope(): void
    {
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);
        $registry = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->toProvider($this->createMock(Provider::class))
            ->service();

        static::assertSame(Scope::SERVICE, $registry->findScope($binding));
    }

    /** @covers ::bind */
    public function testRegisteringWithScopeIsImmutableOriginalReturnsTransient(): void
    {
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);
        $original = new EagerRegistry();
        $original->bind(StubClass::class)->toProvider($this->createMock(Provider::class))->service();

        static::assertSame(Scope::TRANSIENT, $original->findScope($binding));
    }

    /** @covers ::bind */
    public function testCloneableScopeRegistered(): void
    {
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);
        $registry = (new EagerRegistry())
            ->bind(StubClass::class)
            ->toProvider($this->createMock(Provider::class))
            ->cloneable();

        static::assertSame(Scope::CLONEABLE, $registry->findScope($binding));
    }

    /** @covers ::decorate */
    public function testDecorateReturnsDecoratorBinder(): void
    {
        $binder = (new EagerRegistry())->decorate(StubClass::class);

        static::assertInstanceOf(DecoratorBinder::class, $binder);
    }

    /**
     * @covers ::__construct
     * @covers ::decorate
     */
    public function testAfterDecoratingFindDecorationsContainsDecorator(): void
    {
        $decorator = $this->createMock(Decorator::class);
        $binding   = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->decorate(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->withDecorator($decorator)
            ->withDefaultPriority();

        $enhancements = $this->toArray($registry->findDecorations($binding));

        static::assertCount(1, $enhancements);
        static::assertSame($decorator, $enhancements[0]->getValue());
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

    /** @covers ::inject */
    public function testInjectReturnsInjectionBinder(): void
    {
        $binder = (new EagerRegistry())->inject(StubClass::class);

        static::assertInstanceOf(InjectionBinder::class, $binder);
    }

    /**
     * @covers ::__construct
     * @covers ::inject
     */
    public function testAfterInjectingFindInjectionsContainsInjection(): void
    {
        $injection = $this->mockInjection('slot');
        $binding   = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->inject(StubClass::class, Binding::GLOBAL)
            ->withInjection($injection)
            ->withDefaultPriority();

        $enhancements = $this->toArray($registry->findInjections($binding));

        static::assertCount(1, $enhancements);
        static::assertSame($injection, $enhancements[0]->getValue());
    }

    /** @covers ::inject */
    public function testInjectWalksTypeHierarchyToChildBinding(): void
    {
        $injection = $this->mockInjection('slot');
        $binding   = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->inject(StubBase::class)
            ->withInjection($injection)
            ->withDefaultPriority();

        $enhancements = $this->toArray($registry->findInjections($binding));

        static::assertCount(1, $enhancements);
        static::assertSame($injection, $enhancements[0]->getValue());
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

    /** @covers ::call */
    public function testCallReturnsInjectionBinder(): void
    {
        $binder = (new EagerRegistry())->call(StubClass::class);

        static::assertInstanceOf(InjectionBinder::class, $binder);
    }

    /**
     * @covers ::__construct
     * @covers ::call
     */
    public function testAfterCallFindCallsContainsInjection(): void
    {
        $injection = $this->mockInjection('slot');
        $binding   = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->call(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->withInjection($injection)
            ->withDefaultPriority();

        $enhancements = $this->toArray($registry->findCalls($binding));

        static::assertCount(1, $enhancements);
        static::assertSame($injection, $enhancements[0]->getValue());
    }

    /** @covers ::call */
    public function testCallDoesNotWalkTypeHierarchyToChildBinding(): void
    {
        $injection = $this->mockInjection('slot');
        $binding   = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->call(StubBase::class)
            ->withInjection($injection)
            ->withDefaultPriority();

        $enhancements = $this->toArray($registry->findCalls($binding));

        static::assertEmpty($enhancements);
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

    /** @covers ::__construct */
    public function testNewRegistryHasNoProviders(): void
    {
        $registry = new EagerRegistry();
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        static::assertFalse($registry->hasProvider($binding));
        static::assertSame(Scope::TRANSIENT, $registry->findScope($binding));
        static::assertEmpty($this->toArray($registry->findInjections($binding)));
        static::assertEmpty($this->toArray($registry->findCalls($binding)));
        static::assertEmpty($this->toArray($registry->findDecorations($binding)));
    }

    /** @covers ::hasProvider */
    public function testHasProviderReturnsFalseWhenNothingRegistered(): void
    {
        $registry = new EagerRegistry();
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        static::assertFalse($registry->hasProvider($binding));
    }

    /** @covers ::hasProvider */
    public function testHasProviderReturnsTrueForExactBinding(): void
    {
        $provider = $this->createMock(Provider::class);
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->bind(StubClass::class)
            ->toProvider($provider)->transient();

        static::assertTrue($registry->hasProvider($binding));
    }

    /** @covers ::hasProvider */
    public function testHasProviderReturnsFalseWhenProviderOnChildNotParent(): void
    {
        $provider = $this->createMock(Provider::class);
        $binding  = new Binding(StubBase::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->bind(StubClass::class)
            ->toProvider($provider)->transient();

        static::assertFalse($registry->hasProvider($binding));
    }

    /** @covers ::hasProvider */
    public function testHasProviderReturnsFalseWhenProviderRegisteredWithDefaultTagAndLookupUsesCustomTag(): void
    {
        $provider = $this->createMock(Provider::class);
        $binding  = new Binding(StubClass::class, 'custom', Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->toProvider($provider)->transient();

        static::assertFalse($registry->hasProvider($binding));
    }

    /** @covers ::hasProvider */
    public function testHasProviderReturnsTrueWhenGlobalProviderFoundViaNamespaceContextLookup(): void
    {
        $provider = $this->createMock(Provider::class);
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, 'Vivarium\Test\Container');

        $registry = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->toProvider($provider)->transient();

        static::assertTrue($registry->hasProvider($binding));
    }

    /** @covers ::hasProvider */
    public function testHasProviderReturnsFalseForCustomTagAndNamespaceContextWhenOnlyDefaultTagRegistered(): void
    {
        $provider = $this->createMock(Provider::class);
        $binding  = new Binding(StubClass::class, 'custom', 'Vivarium\Test\Container');

        $registry = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->toProvider($provider)->transient();

        static::assertFalse($registry->hasProvider($binding));
    }

    /** @covers ::findProvider */
    public function testFindProviderReturnsRegisteredProvider(): void
    {
        $provider = $this->createMock(Provider::class);
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->bind(StubClass::class)
            ->toProvider($provider)->transient();

        static::assertSame($provider, $registry->findProvider($binding));
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
    public function testFindProviderWidensContextToFindProvider(): void
    {
        $provider = $this->createMock(Provider::class);
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, 'Vivarium\Test\Container');

        $registry = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->toProvider($provider)->transient();

        static::assertSame($provider, $registry->findProvider($binding));
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
    public function testFindProviderThrowsWhenNoProviderFound(): void
    {
        $this->expectException(BindingNotFound::class);

        $registry = new EagerRegistry();
        $registry->findProvider(new Binding(StubClass::class));
    }

    /** @covers ::findScope */
    public function testFindScopeReturnsTransientByDefault(): void
    {
        $registry = new EagerRegistry();
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        static::assertSame(Scope::TRANSIENT, $registry->findScope($binding));
    }

    /** @covers ::findScope */
    public function testFindScopeReturnsRegisteredScopeForExactBinding(): void
    {
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);
        $registry = (new EagerRegistry())
            ->bind(StubClass::class)
            ->toProvider($this->createMock(Provider::class))
            ->service();

        static::assertSame(Scope::SERVICE, $registry->findScope($binding));
    }

    /** @covers ::findScope */
    public function testFindScopeDoesNotWalkHierarchyToParentClass(): void
    {
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);
        $registry = (new EagerRegistry())
            ->bind(StubBase::class)
            ->toProvider($this->createMock(Provider::class))
            ->service();

        static::assertSame(Scope::TRANSIENT, $registry->findScope($binding));
    }

    /** @covers ::findScope */
    public function testFindScopeDoesNotWalkHierarchyToInterface(): void
    {
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);
        $registry = (new EagerRegistry())
            ->bind(Stub::class)
            ->toProvider($this->createMock(Provider::class))
            ->service();

        static::assertSame(Scope::TRANSIENT, $registry->findScope($binding));
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

    /**
     * @param list<string> $registered
     *
     * @covers ::locate
     * @covers ::registeredBinding
     * @covers ::__construct
     * @dataProvider provideLocatableContexts
     */
    public function testLocateReturnsRegisteredBindingWithMostSpecificMatchingContext(
        array $registered,
        string $requested,
        string $expected,
    ): void {
        $registry = new EagerRegistry();

        foreach ($registered as $context) {
            $registry = $registry
                ->bind(StubClass::class, Binding::DEFAULT, $context)
                ->toProvider($this->createMock(Provider::class))
                ->transient();
        }

        $located = $registry->locate(new Binding(StubClass::class, Binding::DEFAULT, $requested));

        static::assertTrue(
            (new Binding(StubClass::class, Binding::DEFAULT, $expected))->equals($located),
        );
    }

    /** @return iterable<string, array{list<string>, string, string}> */
    public static function provideLocatableContexts(): iterable
    {
        yield 'exact global' => [[Binding::GLOBAL], Binding::GLOBAL, Binding::GLOBAL];
        yield 'exact namespace' => [['App\\Orders'], 'App\\Orders', 'App\\Orders'];
        yield 'specific request widens to global' => [[Binding::GLOBAL], 'App\\Orders\\Checkout', Binding::GLOBAL];
        yield 'specific request widens to namespace' => [['App\\Orders'], 'App\\Orders\\Checkout', 'App\\Orders'];
        yield 'most specific registration wins' => [
            [Binding::GLOBAL, 'App\\Orders', 'App\\Orders\\Checkout'],
            'App\\Orders\\Checkout',
            'App\\Orders\\Checkout',
        ];

        yield 'nearest ancestor wins over global' => [
            [Binding::GLOBAL, 'App\\Orders'],
            'App\\Orders\\Checkout',
            'App\\Orders',
        ];
    }

    /**
     * @covers ::locate
     * @covers ::registeredBinding
     * @covers ::__construct
     * @dataProvider provideUnlocatableRequests
     */
    public function testLocateThrowsBindingNotFoundCarryingRequestedBinding(
        string $registeredContext,
        string $requestedContext,
    ): void {
        $registry  = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, $registeredContext)
            ->toProvider($this->createMock(Provider::class))
            ->transient();
        $requested = new Binding(StubWithNoArgs::class, Binding::DEFAULT, $requestedContext);

        try {
            $registry->locate($requested);
            static::fail('Expected BindingNotFound');
        } catch (BindingNotFound $exception) {
            static::assertTrue($requested->equals($exception->getBinding()));
        }
    }

    /** @return iterable<string, array{string, string}> */
    public static function provideUnlocatableRequests(): iterable
    {
        yield 'different type, same context' => [Binding::GLOBAL, Binding::GLOBAL];
        yield 'different type, wider registration' => [Binding::GLOBAL, 'App\\Orders\\Checkout'];
    }

    /**
     * @covers ::locate
     * @covers ::registeredBinding
     * @covers ::__construct
     */
    public function testLocateThrowsBindingNotFoundOnEmptyRegistryWithRequestedContext(): void
    {
        $requested = new Binding(StubClass::class, Binding::DEFAULT, 'App\\Orders\\Checkout');

        try {
            (new EagerRegistry())->locate($requested);
            static::fail('Expected BindingNotFound');
        } catch (BindingNotFound $exception) {
            static::assertTrue($requested->equals($exception->getBinding()));
        }
    }

    /**
     * @covers ::locate
     * @covers ::registeredBinding
     * @covers ::__construct
     */
    public function testTaggedBindingIsFoundAtWiderContextKeepingItsTag(): void
    {
        $registry = (new EagerRegistry())
            ->bind(StubClass::class, 'audit', Binding::GLOBAL)
            ->toProvider($this->createMock(Provider::class))
            ->transient();
        $request  = new Binding(StubClass::class, 'audit', 'App\\Orders');

        $located = $registry->locate($request);

        static::assertTrue($registry->hasProvider($request));
        static::assertTrue((new Binding(StubClass::class, 'audit', Binding::GLOBAL))->equals($located));
    }

    /**
     * @covers ::inject
     * @covers ::findInjections
     * @covers ::__construct
     */
    public function testUntaggedInjectionAppliesToTaggedBinding(): void
    {
        $injection = $this->mockInjection('slot');
        $binding   = new Binding(StubClass::class, 'audit', Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->inject(StubClass::class)
            ->withInjection($injection)
            ->withDefaultPriority();

        $enhancements = $this->toArray($registry->findInjections($binding));

        static::assertCount(1, $enhancements);
        static::assertSame($injection, $enhancements[0]->getValue());
    }

    /**
     * @covers ::bind
     * @covers ::__construct
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

    /**
     * @covers ::define
     * @covers ::bind
     * @covers ::__construct
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
     * @covers ::bind
     * @covers ::__construct
     */
    public function testExtendReplacesARegistrationWithoutDuplicateError(): void
    {
        $first    = $this->createMock(Provider::class);
        $second   = $this->createMock(Provider::class);
        $binding  = new Binding(StubClass::class, 'audit', 'App\\Orders');
        $registry = (new EagerRegistry())
            ->bind(StubClass::class, 'audit', 'App\\Orders')
            ->toProvider($first)
            ->transient();

        $extended = $registry
            ->extend(StubClass::class, 'audit', 'App\\Orders')
            ->toProvider($second)
            ->transient();

        static::assertSame($second, $extended->findProvider($binding));
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
     * @covers ::__construct
     */
    public function testSameSlotOnAMoreSpecificTypeIsAllowed(): void
    {
        $registry = (new EagerRegistry())
            ->inject(StubBase::class)
            ->withInjection($this->mockInjection('slot'))
            ->withDefaultPriority()
            ->inject(StubClass::class)
            ->withInjection($this->mockInjection('slot'))
            ->withDefaultPriority();

        $binding = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        static::assertNotEmpty($this->toArray($registry->findInjections($binding)));
    }

    /**
     * @covers ::inject
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

    /**
     * @covers ::call
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
}
