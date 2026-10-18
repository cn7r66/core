<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Container\Exception\BindingNotFound;
use Vivarium\Container\Exception\ContainerException;
use Vivarium\Container\Exception\ResolutionFailed;
use Vivarium\Container\Injector;
use Vivarium\Container\Registry;
use Vivarium\Container\Registry\EagerRegistry;
use Vivarium\Container\Registry\LazyRegistry;
use Vivarium\Test\Container\Stub\Stub;
use Vivarium\Test\Container\Stub\StubBase;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubContractHook;
use Vivarium\Test\Container\Stub\StubContractHookFactory;
use Vivarium\Test\Container\Stub\StubContractHookImplementation;
use Vivarium\Test\Container\Stub\StubContractHookStaticFactory;
use Vivarium\Test\Container\Stub\StubDecorator;
use Vivarium\Test\Container\Stub\StubEnum;
use Vivarium\Test\Container\Stub\StubModule;
use Vivarium\Test\Container\Stub\StubService;
use Vivarium\Test\Container\Stub\StubUncloneable;
use Vivarium\Test\Container\Stub\StubWithNoArgs;

#[CoversNothing]
final class ContainerEndToEndTest extends TestCase
{
    public function testModuleBootstrapResolvesInterfaceToConcreteClass(): void
    {
        $module    = new StubModule();
        $registry  = new LazyRegistry(static fn (): EagerRegistry => $module->configure(new EagerRegistry()));
        $container = new Injector($registry);

        $result = $container->get(Stub::class);

        self::assertInstanceOf(StubDecorator::class, $result);
        self::assertInstanceOf(StubClass::class, $result->inner);
        self::assertNotNull($result->getService());
    }

    public function testDecoratorIsAppliedInFullPipeline(): void
    {
        $module    = new StubModule();
        $registry  = new LazyRegistry(static fn (): EagerRegistry => $module->configure(new EagerRegistry()));
        $container = new Injector($registry);

        $result = $container->get(Stub::class);

        self::assertInstanceOf(StubDecorator::class, $result);
        self::assertInstanceOf(StubClass::class, $result->inner);
    }

    public function testServiceScopeHoldsAcrossFullBootstrap(): void
    {
        $module    = new StubModule();
        $registry  = new LazyRegistry(static fn (): EagerRegistry => $module->configure(new EagerRegistry()));
        $container = new Injector($registry);

        $first  = $container->get(Stub::class);
        $second = $container->get(Stub::class);

        self::assertSame($first, $second);
    }

    public function testLazyRegistryDefersConstructionUntilFirstGet(): void
    {
        $called   = false;
        $registry = new LazyRegistry(static function () use (&$called): EagerRegistry {
            $called = true;

            return new EagerRegistry();
        });

        $container = new Injector($registry);

        self::assertFalse($called);

        $container->get(StubWithNoArgs::class);

        self::assertTrue($called);
    }

    #[DataProvider('provideInterfaceProvidedRegistries')]
    public function testInjectionOnInterfaceAppliesOnceWhateverProvidesIt(Registry $registry): void
    {
        $result = (new Injector($registry))->get(StubContractHook::class);

        self::assertInstanceOf(StubContractHookImplementation::class, $result);
        self::assertSame(1, $result->calls);
    }

    /** @return array<string, array{Registry}> */
    public static function provideInterfaceProvidedRegistries(): array
    {
        return [
            'implementation class' => [
                (new EagerRegistry())
                    ->bind(StubContractHook::class)->to(StubContractHookImplementation::class)->transient()
                    ->inject(StubContractHook::class)->onMethod('m')->withDefaultPriority(),
            ],
            'factory returning the interface' => [
                (new EagerRegistry())
                    ->bind(StubContractHook::class)
                    ->toFactory(StubContractHookFactory::class)->method('create')->transient()
                    ->inject(StubContractHook::class)->onMethod('m')->withDefaultPriority(),
            ],
            'static factory returning the interface' => [
                (new EagerRegistry())
                    ->bind(StubContractHook::class)
                    ->toStaticFactory(StubContractHookStaticFactory::class)->method('create')->transient()
                    ->inject(StubContractHook::class)->onMethod('m')->withDefaultPriority(),
            ],
            'function returning the interface' => [
                (new EagerRegistry())
                    ->bind(StubContractHook::class)
                    ->toFunction(static fn (): StubContractHook => new StubContractHookImplementation())->transient()
                    ->inject(StubContractHook::class)->onMethod('m')->withDefaultPriority(),
            ],
        ];
    }

    #[DataProvider('provideNullableTypes')]
    public function testNullCanBeBoundAsInstance(string $type): void
    {
        $registry = (new EagerRegistry())
            ->bind($type)->toInstance(null)->service();

        self::assertNull((new Injector($registry))->get($type));
    }

    /** @return array<string, array{string}> */
    public static function provideNullableTypes(): array
    {
        return [
            'null' => ['null'],
            'nullable class' => [StubService::class . '|null'],
        ];
    }

    public function testServiceYieldingNullIsProvidedOnce(): void
    {
        $calls    = 0;
        $registry = (new EagerRegistry())
            ->bind('null')->toFunction(static function () use (&$calls): mixed {
                $calls++;

                return null;
            })->service();

        $container = new Injector($registry);
        $container->get('null');
        $container->get('null');
        $container->get('null');

        self::assertSame(1, $calls);
    }

    public function testCompositionPassesNullIntermediateResult(): void
    {
        $registry = (new EagerRegistry())
            ->bind('callable')->toInstance(static fn (): mixed => null)->service()
            ->decorate('callable')
            ->compose(static fn (mixed $result): string => $result === null ? 'null' : 'value', 'result')
            ->withDefaultPriority();

        $composed = (new Injector($registry))->get('callable');

        self::assertSame('null', $composed());
    }

    public function testExtendOfUnregisteredBindingThrowsBindingNotFound(): void
    {
        $this->expectException(BindingNotFound::class);

        (new EagerRegistry())->extend(StubContractHook::class);
    }

    #[DataProvider('provideUnboundNonInstantiableTypes')]
    public function testUnboundNonInstantiableTypeIsNotFound(string $type): void
    {
        $container = new Injector(new EagerRegistry());

        self::assertFalse($container->has($type));

        try {
            $container->get($type);
            self::fail('Expected ResolutionFailed.');
        } catch (ResolutionFailed $e) {
            self::assertInstanceOf(BindingNotFound::class, $e->getPrevious());
        }
    }

    /** @return array<string, array{string}> */
    public static function provideUnboundNonInstantiableTypes(): array
    {
        return [
            'interface' => [StubContractHook::class],
            'abstract class' => [StubBase::class],
        ];
    }

    #[DataProvider('provideNonCloneableInstances')]
    public function testCloneableScopeRejectsNonCloneableTargetAtRegistration(string $type, mixed $instance): void
    {
        $this->expectException(ContainerException::class);

        (new EagerRegistry())->bind($type)->toInstance($instance)->cloneable();
    }

    /** @return array<string, array{string, mixed}> */
    public static function provideNonCloneableInstances(): array
    {
        return [
            'integer' => ['int', 5],
            'array' => ['array', [1, 2]],
            'enum' => [StubEnum::class, StubEnum::ONLY],
            'class with private __clone' => [StubUncloneable::class, new StubUncloneable()],
        ];
    }

    public function testCloneableScopeServesCopiesOfCloneableClass(): void
    {
        $registry = (new EagerRegistry())
            ->define(StubWithNoArgs::class)->cloneable();

        $container = new Injector($registry);
        $first     = $container->get(StubWithNoArgs::class);
        $second    = $container->get(StubWithNoArgs::class);

        self::assertInstanceOf(StubWithNoArgs::class, $first);
        self::assertEquals($first, $second);
        self::assertNotSame($first, $second);
    }
}
