<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Provider;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Binding;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Provider;
use Vivarium\Container\Provider\Factory;
use Vivarium\Test\Container\Stub\Stub;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubFactory;
use Vivarium\Test\Container\Stub\StubFactoryWithBindableArgs;
use Vivarium\Test\Container\Stub\StubFactoryWithPrivateMethod;
use Vivarium\Test\Container\Stub\StubService;
use Vivarium\Test\Container\Stub\StubStaticFactory;

#[CoversClass(Factory::class)]
final class FactoryTest extends TestCase
{
    public function testProvideCallsInstanceMethodAndReturnsResult(): void
    {
        $factory   = new StubFactory();
        $service   = new StubService();
        $container = $this->createMock(Container::class);
        $container->expects(static::exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($factory, $service);

        $result = (new Factory(new Binding(StubFactory::class), 'create'))->provide($container);

        static::assertInstanceOf(StubClass::class, $result);
        static::assertSame($service, $result->service);
    }

    public function testGetTargetReturnsMethodReturnType(): void
    {
        static::assertSame(
            StubClass::class,
            (new Factory(new Binding(StubFactory::class), 'create'))->getTarget(),
        );
    }

    /** @param class-string $class */
    #[DataProvider('provideUncallableFactoryMethods')]
    public function testThrowsForUncallableFactoryMethod(string $class, string $method): void
    {
        static::expectException(AssertionFailed::class);

        new Factory(new Binding($class), $method);
    }

    /** @return array<string, array{class-string, string}> */
    public static function provideUncallableFactoryMethods(): array
    {
        return [
            'method does not exist' => [StubFactory::class, 'nonExistent'],
            'private method' => [StubFactoryWithPrivateMethod::class, 'create'],
            'method not declared by the bound interface' => [Stub::class, 'create'],
        ];
    }

    public function testGetCapabilitiesReturnsInjectableInterceptableAndDecorable(): void
    {
        $capabilities = (new Factory(new Binding(StubFactory::class), 'create'))->getCapabilities();

        static::assertTrue($capabilities->contains(Capability::INJECTABLE));
        static::assertTrue($capabilities->contains(Capability::INTERCEPTABLE));
        static::assertTrue($capabilities->contains(Capability::DECORABLE));
        static::assertCount(3, $capabilities);
    }

    public function testBindArgument(): void
    {
        $factory = new StubFactoryWithBindableArgs();

        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');
        $provider->method('provide')->willReturn('bound');

        $original = new Factory(new Binding(StubFactoryWithBindableArgs::class), 'create');
        $bound    = $original->bindArgument('name')->toProvider($provider);

        static::assertNotSame($original, $bound);

        $container = $this->createStub(Container::class);
        $container->method('has')->willReturn(false);
        $container->method('get')->willReturn($factory);

        static::assertSame('bound', $bound->provide($container));
        static::assertSame('default', $original->provide($container));
    }

    public function testBindArgumentAtPosition(): void
    {
        $factory = new StubFactoryWithBindableArgs();

        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn('array');
        $provider->method('provide')->willReturn(['a', 'b', 'c']);

        $bound = (new Factory(new Binding(StubFactoryWithBindableArgs::class), 'createVariadic'))
            ->bindArgumentAtPosition(0)
            ->toProvider($provider);

        $container = $this->createStub(Container::class);
        $container->method('get')->willReturn($factory);

        static::assertSame(['a', 'b', 'c'], $bound->provide($container));
    }

    #[DataProvider('provideEqualFactoryPairs')]
    public function testEqualsReturnsTrueForEqualFactories(Factory $first, Factory $second): void
    {
        static::assertTrue($first->equals($second));
    }

    #[DataProvider('provideUnequalPairs')]
    public function testEqualsReturnsFalseForUnequalObjects(Factory $first, object $second): void
    {
        static::assertFalse($first->equals($second));
    }

    #[DataProvider('provideEqualFactoryPairs')]
    public function testHashIsSameForEqualFactories(Factory $first, Factory $second): void
    {
        static::assertSame($first->hash(), $second->hash());
    }

    /** @return array<string, array{Factory, Factory}> */
    public static function provideEqualFactoryPairs(): array
    {
        $instance = new Factory(new Binding(StubFactory::class), 'create');

        $provider = self::createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');
        $provider->method('provide')->willReturn('bound');

        $withoutBinding = new Factory(new Binding(StubFactoryWithBindableArgs::class), 'create');
        $withBinding    = $withoutBinding->bindArgument('name')->toProvider($provider);

        return [
            'same instance' => [$instance, $instance],
            'same binding and method' => [
                new Factory(new Binding(StubFactory::class), 'create'),
                new Factory(new Binding(StubFactory::class), 'create'),
            ],
            'argument bindings excluded from identity' => [$withoutBinding, $withBinding],
        ];
    }

    /** @return array<string, array{Factory, object}> */
    public static function provideUnequalPairs(): array
    {
        return [
            'same binding, different method' => [
                new Factory(new Binding(StubFactoryWithBindableArgs::class), 'create'),
                new Factory(new Binding(StubFactoryWithBindableArgs::class), 'createVariadic'),
            ],
            'different binding, same method' => [
                new Factory(new Binding(StubFactory::class), 'create'),
                new Factory(new Binding(StubStaticFactory::class), 'create'),
            ],
            'different class' => [
                new Factory(new Binding(StubFactory::class), 'create'),
                new stdClass(),
            ],
        ];
    }
}
