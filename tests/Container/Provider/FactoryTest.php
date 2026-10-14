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

/** @coversDefaultClass \Vivarium\Container\Provider\Factory */
final class FactoryTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::provide
     * @covers ::resolveArguments
     */
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

    /** @covers ::getTarget */
    public function testGetTargetReturnsMethodReturnType(): void
    {
        static::assertSame(
            StubClass::class,
            (new Factory(new Binding(StubFactory::class), 'create'))->getTarget(),
        );
    }

    /** @covers ::__construct */
    public function testThrowsForNonExistentMethod(): void
    {
        static::expectException(AssertionFailed::class);

        new Factory(new Binding(StubFactory::class), 'nonExistent');
    }

    /** @covers ::__construct */
    public function testThrowsForPrivateMethod(): void
    {
        static::expectException(AssertionFailed::class);

        new Factory(new Binding(StubFactoryWithPrivateMethod::class), 'create');
    }

    /** @covers ::getCapabilities */
    public function testGetCapabilitiesReturnsInjectableInterceptableAndDecorable(): void
    {
        $capabilities = (new Factory(new Binding(StubFactory::class), 'create'))->getCapabilities();

        static::assertTrue($capabilities->contains(Capability::INJECTABLE));
        static::assertTrue($capabilities->contains(Capability::INTERCEPTABLE));
        static::assertTrue($capabilities->contains(Capability::DECORABLE));
        static::assertCount(3, $capabilities);
    }

    /** @covers ::__construct */
    public function testConstructorException(): void
    {
        static::expectException(AssertionFailed::class);

        new Factory(new Binding(Stub::class), 'create');
    }

    /**
     * @covers ::__construct
     * @covers ::bindArgument
     */
    public function testBindArgument(): void
    {
        $factory = new StubFactoryWithBindableArgs();

        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('string');
        $provider->method('provide')->willReturn('bound');

        $original = new Factory(new Binding(StubFactoryWithBindableArgs::class), 'create');
        $bound    = $original->bindArgument('name')->toProvider($provider);

        static::assertNotSame($original, $bound);

        $container = $this->createMock(Container::class);
        $container->method('has')->willReturn(false);
        $container->method('get')->willReturn($factory);

        static::assertSame('bound', $bound->provide($container));
        static::assertSame('default', $original->provide($container));
    }

    /**
     * @covers ::__construct
     * @covers ::bindArgumentAtPosition
     */
    public function testBindArgumentAtPosition(): void
    {
        $factory = new StubFactoryWithBindableArgs();

        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('array');
        $provider->method('provide')->willReturn(['a', 'b', 'c']);

        $bound = (new Factory(new Binding(StubFactoryWithBindableArgs::class), 'createVariadic'))
            ->bindArgumentAtPosition(0)
            ->toProvider($provider);

        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($factory);

        static::assertSame(['a', 'b', 'c'], $bound->provide($container));
    }

    /**
     * @dataProvider provideEqualsCases
     * @covers ::__construct
     * @covers ::equals
     */
    public function testEquals(Factory $a, object $b, bool $expected): void
    {
        static::assertSame($expected, $a->equals($b));
    }

    /**
     * @dataProvider provideEqualFactoryPairs
     * @covers ::__construct
     * @covers ::hash
     */
    public function testHash(Factory $a, Factory $b): void
    {
        static::assertSame($a->hash(), $b->hash());
    }

    /** @return iterable<string, array{0: Factory, 1: object, 2: bool}> */
    public static function provideEqualsCases(): iterable
    {
        $reflexive = new Factory(new Binding(StubFactory::class), 'create');

        yield 'same instance' => [$reflexive, $reflexive, true];

        yield 'same binding and method' => [
            new Factory(new Binding(StubFactory::class), 'create'),
            new Factory(new Binding(StubFactory::class), 'create'),
            true,
        ];

        yield 'same binding, different method' => [
            new Factory(new Binding(StubFactoryWithBindableArgs::class), 'create'),
            new Factory(new Binding(StubFactoryWithBindableArgs::class), 'createVariadic'),
            false,
        ];

        yield 'different binding, same method' => [
            new Factory(new Binding(StubFactory::class), 'create'),
            new Factory(new Binding(StubStaticFactory::class), 'create'),
            false,
        ];

        yield 'different class' => [
            new Factory(new Binding(StubFactory::class), 'create'),
            new stdClass(),
            false,
        ];

        $provider = self::createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');
        $provider->method('provide')->willReturn('bound');

        $withoutBinding = new Factory(new Binding(StubFactoryWithBindableArgs::class), 'create');
        $withBinding    = $withoutBinding->bindArgument('name')->toProvider($provider);

        yield 'argument bindings excluded from identity' => [$withoutBinding, $withBinding, true];
    }

    /** @return iterable<string, array{0: Factory, 1: Factory}> */
    public static function provideEqualFactoryPairs(): iterable
    {
        foreach (self::provideEqualsCases() as $key => $case) {
            if ($case[2] !== true) {
                continue;
            }

            if (! $case[1] instanceof Factory) {
                continue;
            }

            yield $key => [$case[0], $case[1]];
        }
    }
}
