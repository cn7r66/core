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
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Provider;
use Vivarium\Container\Provider\StaticFactory;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubFactory;
use Vivarium\Test\Container\Stub\StubService;
use Vivarium\Test\Container\Stub\StubStaticFactory;
use Vivarium\Test\Container\Stub\StubStaticFactoryWithBindableArgs;
use Vivarium\Test\Container\Stub\StubStaticFactoryWithPrivateMethod;

#[CoversClass(StaticFactory::class)]
final class StaticFactoryTest extends TestCase
{
    public function testProvideCallsStaticMethodAndReturnsResult(): void
    {
        $service   = new StubService();
        $container = $this->createStub(Container::class);
        $container->method('get')->willReturn($service);

        $result = (new StaticFactory(StubStaticFactory::class, 'create'))->provide($container);

        static::assertInstanceOf(StubClass::class, $result);
        static::assertSame($service, $result->service);
    }

    public function testGetTargetReturnsMethodReturnType(): void
    {
        static::assertSame(
            StubClass::class,
            (new StaticFactory(StubStaticFactory::class, 'create'))->getTarget(),
        );
    }

    /** @param class-string $class */
    #[DataProvider('provideUncallableStaticMethods')]
    public function testThrowsForUncallableStaticMethod(string $class, string $method): void
    {
        static::expectException(AssertionFailed::class);

        new StaticFactory($class, $method);
    }

    /** @return array<string, array{class-string, string}> */
    public static function provideUncallableStaticMethods(): array
    {
        return [
            'method does not exist' => [StubStaticFactory::class, 'nonExistent'],
            'private method' => [StubStaticFactoryWithPrivateMethod::class, 'create'],
            'non-static method' => [StubFactory::class, 'create'],
        ];
    }

    public function testGetCapabilitiesContainsInjectableInterceptableAndDecorable(): void
    {
        $capabilities = (new StaticFactory(StubStaticFactory::class, 'create'))->getCapabilities();

        static::assertTrue($capabilities->contains(Capability::INJECTABLE));
        static::assertTrue($capabilities->contains(Capability::INTERCEPTABLE));
        static::assertTrue($capabilities->contains(Capability::DECORABLE));
        static::assertCount(3, $capabilities);
    }

    public function testBindArgument(): void
    {
        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');
        $provider->method('provide')->willReturn('bound');

        $original = new StaticFactory(StubStaticFactoryWithBindableArgs::class, 'create');
        $bound    = $original->bindArgument('name')->toProvider($provider);

        static::assertNotSame($original, $bound);

        $container = $this->createStub(Container::class);

        static::assertSame('bound', $bound->provide($container));
        static::assertSame('default', $original->provide($container));
    }

    public function testBindArgumentAtPosition(): void
    {
        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn('array');
        $provider->method('provide')->willReturn(['a', 'b', 'c']);

        $bound = (new StaticFactory(StubStaticFactoryWithBindableArgs::class, 'createVariadic'))
            ->bindArgumentAtPosition(0)
            ->toProvider($provider);

        $container = $this->createStub(Container::class);

        static::assertSame(['a', 'b', 'c'], $bound->provide($container));
    }

    #[DataProvider('provideEqualStaticFactoryPairs')]
    public function testEqualsReturnsTrueForEqualStaticFactories(StaticFactory $first, StaticFactory $second): void
    {
        static::assertTrue($first->equals($second));
    }

    #[DataProvider('provideUnequalPairs')]
    public function testEqualsReturnsFalseForUnequalObjects(StaticFactory $first, object $second): void
    {
        static::assertFalse($first->equals($second));
    }

    #[DataProvider('provideEqualStaticFactoryPairs')]
    public function testHashIsSameForEqualStaticFactories(StaticFactory $first, StaticFactory $second): void
    {
        static::assertSame($first->hash(), $second->hash());
    }

    /** @return array<string, array{StaticFactory, StaticFactory}> */
    public static function provideEqualStaticFactoryPairs(): array
    {
        $instance = new StaticFactory(StubStaticFactory::class, 'create');

        $provider = self::createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');
        $provider->method('provide')->willReturn('bound');

        $withoutBinding = new StaticFactory(StubStaticFactoryWithBindableArgs::class, 'create');
        $withBinding    = $withoutBinding->bindArgument('name')->toProvider($provider);

        return [
            'same instance' => [$instance, $instance],
            'same class and method' => [
                new StaticFactory(StubStaticFactory::class, 'create'),
                new StaticFactory(StubStaticFactory::class, 'create'),
            ],
            'argument bindings excluded from identity' => [$withoutBinding, $withBinding],
        ];
    }

    /** @return array<string, array{StaticFactory, object}> */
    public static function provideUnequalPairs(): array
    {
        return [
            'same class, different method' => [
                new StaticFactory(StubStaticFactoryWithBindableArgs::class, 'create'),
                new StaticFactory(StubStaticFactoryWithBindableArgs::class, 'createVariadic'),
            ],
            'different class, same method' => [
                new StaticFactory(StubStaticFactory::class, 'create'),
                new StaticFactory(StubStaticFactoryWithBindableArgs::class, 'create'),
            ],
            'different class' => [
                new StaticFactory(StubStaticFactory::class, 'create'),
                new stdClass(),
            ],
        ];
    }
}
