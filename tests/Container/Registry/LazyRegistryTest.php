<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Registry;

use PHPUnit\Framework\TestCase;
use Vivarium\Container\Binding;
use Vivarium\Container\Decorator;
use Vivarium\Container\Injection;
use Vivarium\Container\Provider;
use Vivarium\Container\Registry;
use Vivarium\Container\Registry\LazyRegistry;
use Vivarium\Container\Scope;
use Vivarium\Test\Container\Stub\StubClass;

/** @coversDefaultClass \Vivarium\Container\Registry\LazyRegistry */
final class LazyRegistryTest extends TestCase
{
    /** @covers ::__construct */
    public function testFactoryIsNotCalledAtConstruction(): void
    {
        $called = 0;

        new LazyRegistry(static function () use (&$called): Registry {
            $called++;

            return self::createMock(Registry::class);
        });

        self::assertSame(0, $called);
    }

    /**
     * @covers ::hasProvider
     * @covers ::getRealRegistry
     */
    public function testFactoryIsCalledOnFirstMethodCall(): void
    {
        $called  = 0;
        $binding = new Binding(StubClass::class);
        $inner   = $this->createMock(Registry::class);
        $inner->method('hasProvider')->willReturn(false);

        $registry = new LazyRegistry(static function () use (&$called, $inner): Registry {
            $called++;

            return $inner;
        });

        $registry->hasProvider($binding);

        self::assertSame(1, $called);
    }

    /**
     * @covers ::hasProvider
     * @covers ::findProvider
     * @covers ::findScope
     * @covers ::findInjections
     * @covers ::findCalls
     * @covers ::findDecorations
     * @covers ::getRealRegistry
     */
    public function testFactoryIsCalledOnlyOnce(): void
    {
        $called  = 0;
        $binding = new Binding(StubClass::class);
        $inner   = $this->createMock(Registry::class);
        $inner->method('hasProvider')->willReturn(false);
        $inner->method('findScope')->willReturn(Scope::SERVICE);
        $inner->method('findInjections')->willReturn([]);
        $inner->method('findCalls')->willReturn([]);
        $inner->method('findDecorations')->willReturn([]);

        $registry = new LazyRegistry(static function () use (&$called, $inner): Registry {
            $called++;

            return $inner;
        });

        $registry->hasProvider($binding);
        $registry->findScope($binding);
        $registry->findInjections($binding);
        $registry->findCalls($binding);
        $registry->findDecorations($binding);

        self::assertSame(1, $called);
    }

    /**
     * @covers ::hasProvider
     * @covers ::getRealRegistry
     */
    public function testHasProviderDelegatesToInnerRegistry(): void
    {
        $binding = new Binding(StubClass::class);
        $inner   = $this->createMock(Registry::class);
        $inner->expects($this->once())
            ->method('hasProvider')
            ->with($binding)
            ->willReturn(true);

        $registry = new LazyRegistry(static fn (): Registry => $inner);

        self::assertTrue($registry->hasProvider($binding));
    }

    /**
     * @covers ::hasProvider
     * @covers ::getRealRegistry
     */
    public function testHasProviderReturnsFalseWhenInnerRegistryReturnsFalse(): void
    {
        $binding = new Binding(StubClass::class);
        $inner   = $this->createMock(Registry::class);
        $inner->method('hasProvider')->willReturn(false);

        $registry = new LazyRegistry(static fn (): Registry => $inner);

        self::assertFalse($registry->hasProvider($binding));
    }

    /**
     * @covers ::findProvider
     * @covers ::getRealRegistry
     */
    public function testFindProviderDelegatesToInnerRegistry(): void
    {
        $binding  = new Binding(StubClass::class);
        $provider = $this->createMock(Provider::class);
        $inner    = $this->createMock(Registry::class);
        $inner->expects($this->once())
            ->method('findProvider')
            ->with($binding)
            ->willReturn($provider);

        $registry = new LazyRegistry(static fn (): Registry => $inner);

        self::assertSame($provider, $registry->findProvider($binding));
    }

    /**
     * @covers ::findScope
     * @covers ::getRealRegistry
     */
    public function testFindScopeDelegatesToInnerRegistry(): void
    {
        $binding = new Binding(StubClass::class);
        $inner   = $this->createMock(Registry::class);
        $inner->expects($this->once())
            ->method('findScope')
            ->with($binding)
            ->willReturn(Scope::SERVICE);

        $registry = new LazyRegistry(static fn (): Registry => $inner);

        self::assertSame(Scope::SERVICE, $registry->findScope($binding));
    }

    /**
     * @covers ::findInjections
     * @covers ::getRealRegistry
     */
    public function testFindInjectionsDelegatesToInnerRegistry(): void
    {
        $binding   = new Binding(StubClass::class);
        $injection = $this->createMock(Injection::class);
        $inner     = $this->createMock(Registry::class);
        $inner->expects($this->once())
            ->method('findInjections')
            ->with($binding)
            ->willReturn([$injection]);

        $registry = new LazyRegistry(static fn (): Registry => $inner);

        self::assertSame([$injection], $registry->findInjections($binding));
    }

    /**
     * @covers ::findCalls
     * @covers ::getRealRegistry
     */
    public function testFindCallsDelegatesToInnerRegistry(): void
    {
        $binding = new Binding(StubClass::class);
        $call    = $this->createMock(Injection::class);
        $inner   = $this->createMock(Registry::class);
        $inner->expects($this->once())
            ->method('findCalls')
            ->with($binding)
            ->willReturn([$call]);

        $registry = new LazyRegistry(static fn (): Registry => $inner);

        self::assertSame([$call], $registry->findCalls($binding));
    }

    /**
     * @covers ::findDecorations
     * @covers ::getRealRegistry
     */
    public function testFindDecorationsDelegatesToInnerRegistry(): void
    {
        $binding   = new Binding(StubClass::class);
        $decorator = $this->createMock(Decorator::class);
        $inner     = $this->createMock(Registry::class);
        $inner->expects($this->once())
            ->method('findDecorations')
            ->with($binding)
            ->willReturn([$decorator]);

        $registry = new LazyRegistry(static fn (): Registry => $inner);

        self::assertSame([$decorator], $registry->findDecorations($binding));
    }

    /**
     * @covers ::locate
     * @covers ::getRealRegistry
     * @covers ::__construct
     */
    public function testLocateDelegatesToInnerRegistry(): void
    {
        $binding = new Binding(StubClass::class);
        $located = new Binding(StubClass::class);
        $inner   = $this->createMock(Registry::class);
        $inner->expects($this->once())
            ->method('locate')
            ->with($binding)
            ->willReturn($located);

        $registry = new LazyRegistry(static fn (): Registry => $inner);

        self::assertSame($located, $registry->locate($binding));
    }
}
