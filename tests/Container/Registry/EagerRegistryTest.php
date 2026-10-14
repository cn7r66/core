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
use Vivarium\Container\Injection;
use Vivarium\Container\Provider;
use Vivarium\Container\Registry\EagerRegistry;
use Vivarium\Container\Scope;
use Vivarium\Test\Container\Stub\Stub;
use Vivarium\Test\Container\Stub\StubBase;
use Vivarium\Test\Container\Stub\StubClass;
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
    public function testAfterDecoratingFindEnhancementsContainsDecorator(): void
    {
        $decorator = $this->createMock(Decorator::class);
        $binding   = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->decorate(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->withDecorator($decorator)
            ->withDefaultPriority();

        $enhancements = $this->toArray($registry->findEnhancements($binding));

        static::assertCount(1, $enhancements);
        static::assertSame($decorator, $enhancements[0]->getValue());
    }

    /** @covers ::decorate */
    public function testDecorateIsImmutableOriginalHasNoEnhancements(): void
    {
        $decorator = $this->createMock(Decorator::class);
        $binding   = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $original = new EagerRegistry();
        $original->decorate(StubClass::class)->withDecorator($decorator)->withDefaultPriority();

        static::assertEmpty($this->toArray($original->findEnhancements($binding)));
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
    public function testAfterInjectingFindEnhancementsContainsInjection(): void
    {
        $injection = $this->mockInjection('slot');
        $binding   = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->inject(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->withInjection($injection)
            ->withDefaultPriority();

        $enhancements = $this->toArray($registry->findEnhancements($binding));

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

        $enhancements = $this->toArray($registry->findEnhancements($binding));

        static::assertCount(1, $enhancements);
        static::assertSame($injection, $enhancements[0]->getValue());
    }

    /** @covers ::inject */
    public function testInjectIsImmutableOriginalHasNoEnhancements(): void
    {
        $injection = $this->mockInjection('slot');
        $binding   = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $original = new EagerRegistry();
        $original->inject(StubClass::class)->withInjection($injection)->withDefaultPriority();

        static::assertEmpty($this->toArray($original->findEnhancements($binding)));
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
    public function testAfterCallFindEnhancementsContainsInjection(): void
    {
        $injection = $this->mockInjection('slot');
        $binding   = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->call(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->withInjection($injection)
            ->withDefaultPriority();

        $enhancements = $this->toArray($registry->findEnhancements($binding));

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

        $enhancements = $this->toArray($registry->findEnhancements($binding));

        static::assertEmpty($enhancements);
    }

    /** @covers ::call */
    public function testCallIsImmutableOriginalHasNoEnhancements(): void
    {
        $injection = $this->mockInjection('slot');
        $binding   = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        $original = new EagerRegistry();
        $original->call(StubClass::class)->withInjection($injection)->withDefaultPriority();

        static::assertEmpty($this->toArray($original->findEnhancements($binding)));
    }

    /** @covers ::__construct */
    public function testNewRegistryHasNoProviders(): void
    {
        $registry = new EagerRegistry();
        $binding  = new Binding(StubClass::class, Binding::DEFAULT, Binding::GLOBAL);

        static::assertFalse($registry->hasProvider($binding));
        static::assertSame(Scope::TRANSIENT, $registry->findScope($binding));
        static::assertEmpty($this->toArray($registry->findEnhancements($binding)));
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
    public function testHasProviderReturnsTrueWhenProviderRegisteredWithDefaultTagAndLookupUsesCustomTag(): void
    {
        $provider = $this->createMock(Provider::class);
        $binding  = new Binding(StubClass::class, 'custom', Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->toProvider($provider)->transient();

        static::assertTrue($registry->hasProvider($binding));
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
    public function testHasProviderReturnsTrueWhenFoundViaCustomTagAndNamespaceContextLookup(): void
    {
        $provider = $this->createMock(Provider::class);
        $binding  = new Binding(StubClass::class, 'custom', 'Vivarium\Test\Container');

        $registry = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->toProvider($provider)->transient();

        static::assertTrue($registry->hasProvider($binding));
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
    public function testFindProviderWidensTagToFindProvider(): void
    {
        $provider = $this->createMock(Provider::class);
        $binding  = new Binding(StubClass::class, 'custom', Binding::GLOBAL);

        $registry = (new EagerRegistry())
            ->bind(StubClass::class, Binding::DEFAULT, Binding::GLOBAL)
            ->toProvider($provider)->transient();

        static::assertSame($provider, $registry->findProvider($binding));
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
}
