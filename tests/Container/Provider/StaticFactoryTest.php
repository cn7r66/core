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

    /** @covers ::__construct */
    public function testThrowsForNonExistentMethod(): void
    {
        static::expectException(AssertionFailed::class);

        new StaticFactory(StubStaticFactory::class, 'nonExistent');
    }

    /** @covers ::__construct */
    public function testThrowsForPrivateMethod(): void
    {
        static::expectException(AssertionFailed::class);

        new StaticFactory(StubStaticFactoryWithPrivateMethod::class, 'create');
    }

    /**
     * @covers ::__construct
     * @covers ::getCapabilities
     */
    public function testGetCapabilitiesContainsInterceptableAndDecorable(): void
    {
        $capabilities = (new StaticFactory(StubStaticFactory::class, 'create'))->getCapabilities();

        static::assertFalse($capabilities->contains(Capability::INJECTABLE));
        static::assertTrue($capabilities->contains(Capability::INTERCEPTABLE));
        static::assertTrue($capabilities->contains(Capability::DECORABLE));
        static::assertCount(2, $capabilities);
    }

    /** @covers ::__construct */
    public function testConstructorException(): void
    {
        static::expectException(AssertionFailed::class);

        new StaticFactory(StubFactory::class, 'create');
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
     * @dataProvider provideEqualsCases
     * @covers ::__construct
     * @covers ::equals
     */
    public function testEquals(StaticFactory $a, object $b, bool $expected): void
    {
        static::assertSame($expected, $a->equals($b));
    }

    /**
     * @dataProvider provideEqualStaticFactoryPairs
     * @covers ::__construct
     * @covers ::hash
     */
    public function testHash(StaticFactory $a, StaticFactory $b): void
    {
        static::assertSame($a->hash(), $b->hash());
    }

    /** @return iterable<string, array{0: StaticFactory, 1: object, 2: bool}> */
    public static function provideEqualsCases(): iterable
    {
        $reflexive = new StaticFactory(StubStaticFactory::class, 'create');

        yield 'same instance' => [$reflexive, $reflexive, true];

        yield 'same class and method' => [
            new StaticFactory(StubStaticFactory::class, 'create'),
            new StaticFactory(StubStaticFactory::class, 'create'),
            true,
        ];

        yield 'same class, different method' => [
            new StaticFactory(StubStaticFactoryWithBindableArgs::class, 'create'),
            new StaticFactory(StubStaticFactoryWithBindableArgs::class, 'createVariadic'),
            false,
        ];

        yield 'different class, same method' => [
            new StaticFactory(StubStaticFactory::class, 'create'),
            new StaticFactory(StubStaticFactoryWithBindableArgs::class, 'create'),
            false,
        ];

        yield 'different class' => [
            new StaticFactory(StubStaticFactory::class, 'create'),
            new stdClass(),
            false,
        ];

        $provider = self::createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');
        $provider->method('provide')->willReturn('bound');

        $withoutBinding = new StaticFactory(StubStaticFactoryWithBindableArgs::class, 'create');
        $withBinding    = $withoutBinding->bindArgument('name')->toProvider($provider);

        yield 'argument bindings excluded from identity' => [$withoutBinding, $withBinding, true];
    }

    /** @return iterable<string, array{0: StaticFactory, 1: StaticFactory}> */
    public static function provideEqualStaticFactoryPairs(): iterable
    {
        foreach (self::provideEqualsCases() as $key => $case) {
            if ($case[2] !== true) {
                continue;
            }

            if (! $case[1] instanceof StaticFactory) {
                continue;
            }

            yield $key => [$case[0], $case[1]];
        }
    }
}
