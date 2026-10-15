<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Provider;

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

/** @coversDefaultClass \Vivarium\Container\Provider\StaticFactory */
final class StaticFactoryTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::provide
     */
    public function testProvideCallsStaticMethodAndReturnsResult(): void
    {
        $service   = new StubService();
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($service);

        $result = (new StaticFactory(StubStaticFactory::class, 'create'))->provide($container);

        static::assertInstanceOf(StubClass::class, $result);
        static::assertSame($service, $result->service);
    }

    /**
     * @covers ::__construct
     * @covers ::getTarget
     */
    public function testGetTargetReturnsMethodReturnType(): void
    {
        static::assertSame(
            StubClass::class,
            (new StaticFactory(StubStaticFactory::class, 'create'))->getTarget(),
        );
    }

    /**
     * @param class-string $class
     *
     * @dataProvider provideUncallableStaticMethods
     * @covers ::__construct
     */
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

    /**
     * @covers ::__construct
     * @covers ::getCapabilities
     */
    public function testGetCapabilitiesContainsInjectableInterceptableAndDecorable(): void
    {
        $capabilities = (new StaticFactory(StubStaticFactory::class, 'create'))->getCapabilities();

        static::assertTrue($capabilities->contains(Capability::INJECTABLE));
        static::assertTrue($capabilities->contains(Capability::INTERCEPTABLE));
        static::assertTrue($capabilities->contains(Capability::DECORABLE));
        static::assertCount(3, $capabilities);
    }

    /**
     * @covers ::__construct
     * @covers ::bindArgument
     */
    public function testBindArgument(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('string');
        $provider->method('provide')->willReturn('bound');

        $original = new StaticFactory(StubStaticFactoryWithBindableArgs::class, 'create');
        $bound    = $original->bindArgument('name')->toProvider($provider);

        static::assertNotSame($original, $bound);

        $container = $this->createMock(Container::class);

        static::assertSame('bound', $bound->provide($container));
        static::assertSame('default', $original->provide($container));
    }

    /**
     * @covers ::__construct
     * @covers ::bindArgumentAtPosition
     */
    public function testBindArgumentAtPosition(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('array');
        $provider->method('provide')->willReturn(['a', 'b', 'c']);

        $bound = (new StaticFactory(StubStaticFactoryWithBindableArgs::class, 'createVariadic'))
            ->bindArgumentAtPosition(0)
            ->toProvider($provider);

        $container = $this->createMock(Container::class);

        static::assertSame(['a', 'b', 'c'], $bound->provide($container));
    }

    /**
     * @dataProvider provideEqualStaticFactoryPairs
     * @covers ::__construct
     * @covers ::equals
     */
    public function testEqualsReturnsTrueForEqualStaticFactories(StaticFactory $first, StaticFactory $second): void
    {
        static::assertTrue($first->equals($second));
    }

    /**
     * @dataProvider provideUnequalPairs
     * @covers ::__construct
     * @covers ::equals
     */
    public function testEqualsReturnsFalseForUnequalObjects(StaticFactory $first, object $second): void
    {
        static::assertFalse($first->equals($second));
    }

    /**
     * @dataProvider provideEqualStaticFactoryPairs
     * @covers ::__construct
     * @covers ::hash
     */
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
