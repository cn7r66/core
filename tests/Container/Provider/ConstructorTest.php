<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Provider;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Provider;
use Vivarium\Container\Provider\Constructor;
use Vivarium\Test\Container\Stub\NotInstantiableStub;
use Vivarium\Test\Container\Stub\Stub;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubService;
use Vivarium\Test\Container\Stub\StubWithMultipleArgs;
use Vivarium\Test\Container\Stub\StubWithNoArgs;
use Vivarium\Test\Container\Stub\StubWithNoConstructor;
use Vivarium\Test\Container\Stub\StubWithOptionalArg;
use Vivarium\Test\Container\Stub\StubWithUntypedOptionalArg;
use Vivarium\Test\Container\Stub\StubWithUntypedRequiredArg;
use Vivarium\Test\Container\Stub\StubWithVariadicArgs;

#[CoversClass(Constructor::class)]
final class ConstructorTest extends TestCase
{
    public function testProvideCreatesInstanceWithDependency(): void
    {
        $service   = new StubService();
        $container = $this->createStub(Container::class);
        $container->method('get')->willReturn($service);

        $result = (new Constructor(StubClass::class))->provide($container);

        static::assertInstanceOf(StubClass::class, $result);
        static::assertSame($service, $result->service);
    }

    /** @param class-string $class */
    #[DataProvider('provideClassesWithoutParameters')]
    public function testProvideCreatesInstanceWithoutResolvingAnyArgument(string $class): void
    {
        $container = $this->createMock(Container::class);
        $container->expects(static::never())->method('get');

        $result = (new Constructor($class))->provide($container);

        static::assertInstanceOf($class, $result);
    }

    /** @return array<string, array{class-string}> */
    public static function provideClassesWithoutParameters(): array
    {
        return [
            'constructor without parameters' => [StubWithNoArgs::class],
            'no explicit constructor' => [StubWithNoConstructor::class],
        ];
    }

    public function testProvideUsesDefaultWhenOptionalArgNotInContainer(): void
    {
        $container = $this->createStub(Container::class);
        $container->method('has')->willReturn(false);

        $result = (new Constructor(StubWithOptionalArg::class))->provide($container);

        static::assertInstanceOf(StubWithOptionalArg::class, $result);
        static::assertSame('default', $result->name);
    }

    public function testProvideUsesContainerValueForOptionalArg(): void
    {
        $container = $this->createStub(Container::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn('custom');

        $result = (new Constructor(StubWithOptionalArg::class))->provide($container);

        static::assertSame('custom', $result->name);
    }

    public function testProvideWithUntypedOptionalArgUsesDefault(): void
    {
        $container = $this->createMock(Container::class);
        $container->expects(static::never())->method('get');

        $result = (new Constructor(StubWithUntypedOptionalArg::class))->provide($container);

        static::assertSame('default', $result->getValue());
    }

    public function testProvideResolvesUntypedRequiredArgFromContainer(): void
    {
        $container = $this->createStub(Container::class);
        $container->method('get')->willReturn('resolved');

        $result = (new Constructor(StubWithUntypedRequiredArg::class))->provide($container);

        static::assertSame('resolved', $result->getValue());
    }

    public function testProvidePrefersExplicitlyBoundArgumentOverAutoResolution(): void
    {
        $service = new StubService();
        $custom  = $this->createStub(Provider::class);
        $custom->method('getTarget')->willReturn(StubService::class);
        $custom->method('provide')->willReturn($service);

        $container = $this->createMock(Container::class);
        $container->expects(static::never())->method('get');

        $result = (new Constructor(StubClass::class))
            ->bindArgument('service')
            ->toProvider($custom)
            ->provide($container);

        static::assertInstanceOf(StubClass::class, $result);
        static::assertSame($service, $result->service);
    }

    /** @param class-string $class */
    #[DataProvider('provideConstructibleClasses')]
    public function testGetTargetReturnsClassName(string $class): void
    {
        static::assertSame($class, (new Constructor($class))->getTarget());
    }

    /** @return array<string, array{class-string}> */
    public static function provideConstructibleClasses(): array
    {
        return [
            'constructor with a dependency' => [StubClass::class],
            'constructor with multiple parameters' => [StubWithMultipleArgs::class],
            'no explicit constructor' => [StubWithNoConstructor::class],
        ];
    }

    /** @param class-string $class */
    #[DataProvider('provideClassesWithAndWithoutConstructor')]
    public function testGetCapabilitiesIncludesAll(string $class): void
    {
        $capabilities = (new Constructor($class))->getCapabilities();

        static::assertTrue($capabilities->contains(Capability::INJECTABLE));
        static::assertTrue($capabilities->contains(Capability::INTERCEPTABLE));
        static::assertTrue($capabilities->contains(Capability::DECORABLE));
        static::assertCount(3, $capabilities);
    }

    /** @return array<string, array{class-string}> */
    public static function provideClassesWithAndWithoutConstructor(): array
    {
        return [
            'constructor with a dependency' => [StubClass::class],
            'no explicit constructor' => [StubWithNoConstructor::class],
        ];
    }

    /** @param class-string $class */
    #[DataProvider('provideNotInstantiableClasses')]
    public function testThrowsForNotInstantiableClass(string $class): void
    {
        static::expectException(AssertionFailed::class);

        new Constructor($class);
    }

    /** @return array<string, array{class-string}> */
    public static function provideNotInstantiableClasses(): array
    {
        return [
            'private constructor' => [NotInstantiableStub::class],
            'interface' => [Stub::class],
        ];
    }

    /** @param class-string $class */
    #[DataProvider('provideUnknownParameterNames')]
    public function testBindArgumentThrowsForUnknownParameterName(string $class, string $name): void
    {
        static::expectException(AssertionFailed::class);

        (new Constructor($class))->bindArgument($name);
    }

    /** @return array<string, array{class-string, string}> */
    public static function provideUnknownParameterNames(): array
    {
        return [
            'name not among the parameters' => [StubClass::class, 'nonExistent'],
            'class without constructor' => [StubWithNoConstructor::class, 'value'],
        ];
    }

    /** @param class-string $class */
    #[DataProvider('provideUnknownParameterPositions')]
    public function testBindArgumentAtPositionThrowsForUnknownPosition(string $class, int $position): void
    {
        static::expectException(AssertionFailed::class);

        (new Constructor($class))->bindArgumentAtPosition($position);
    }

    /** @return array<string, array{class-string, int}> */
    public static function provideUnknownParameterPositions(): array
    {
        return [
            'position out of bounds' => [StubWithMultipleArgs::class, 3],
            'negative position' => [StubWithMultipleArgs::class, -1],
            'class without constructor' => [StubWithNoConstructor::class, 0],
        ];
    }

    #[DataProvider('provideEqualConstructors')]
    public function testEqualsReturnsTrueForEqualConstructors(Constructor $first, Constructor $second): void
    {
        static::assertTrue($first->equals($second));
    }

    /** @return array<string, array{Constructor, Constructor}> */
    public static function provideEqualConstructors(): array
    {
        $instance = new Constructor(StubClass::class);

        return [
            'two instances of the same class' => [
                new Constructor(StubClass::class),
                new Constructor(StubClass::class),
            ],
            'same instance' => [$instance, $instance],
        ];
    }

    public function testEqualsReturnsFalseForDifferentClass(): void
    {
        $first  = new Constructor(StubClass::class);
        $second = new Constructor(StubWithNoArgs::class);

        static::assertFalse($first->equals($second));
    }

    public function testEqualsReturnsFalseForNonMethod(): void
    {
        static::assertFalse(
            (new Constructor(StubClass::class))->equals(new stdClass()),
        );
    }

    #[DataProvider('provideEqualConstructors')]
    public function testHashIsSameForEqualConstructors(Constructor $first, Constructor $second): void
    {
        static::assertSame($first->hash(), $second->hash());
    }

    public function testHashDiffersForDifferentClasses(): void
    {
        $first  = new Constructor(StubClass::class);
        $second = new Constructor(StubWithNoArgs::class);

        static::assertNotSame($first->hash(), $second->hash());
    }

    public function testBindArgument(): void
    {
        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');
        $provider->method('provide')->willReturn('bound');

        $original = new Constructor(StubWithOptionalArg::class);
        $bound    = $original->bindArgument('name')->toProvider($provider);

        static::assertNotSame($original, $bound);

        $container = $this->createStub(Container::class);
        $container->method('has')->willReturn(false);

        static::assertSame('bound', $bound->provide($container)->name);
        static::assertSame('default', $original->provide($container)->name);
    }

    public function testBindArgumentException(): void
    {
        static::expectException(AssertionFailed::class);

        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');

        (new Constructor(StubClass::class))->bindArgument('service')->toProvider($provider);
    }

    public function testBindArgumentAtPosition(): void
    {
        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn('array');
        $provider->method('provide')->willReturn(['a', 'b', 'c']);

        $constructor = (new Constructor(StubWithVariadicArgs::class))
            ->bindArgumentAtPosition(0)
            ->toProvider($provider);

        $container = $this->createStub(Container::class);

        $result = $constructor->provide($container);

        static::assertSame(['a', 'b', 'c'], $result->names);
    }

    public function testProvide(): void
    {
        $container = $this->createStub(Container::class);

        $result = (new Constructor(StubWithVariadicArgs::class))->provide($container);

        static::assertSame([], $result->names);
    }

    public function testEquals(): void
    {
        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);

        $unbound = new Constructor(StubClass::class);
        $bound   = (new Constructor(StubClass::class))->bindArgument('service')->toProvider($provider);

        static::assertTrue($unbound->equals($bound));
    }

    public function testHash(): void
    {
        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn(StubService::class);

        $unbound = new Constructor(StubClass::class);
        $bound   = (new Constructor(StubClass::class))->bindArgument('service')->toProvider($provider);

        static::assertSame($unbound->hash(), $bound->hash());
    }
}
