<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Injection;

use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Collection\Set\HashSet;
use Vivarium\Container\Binding;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Injection\SetProperty;
use Vivarium\Container\Provider;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubService;

/** @coversDefaultClass \Vivarium\Container\Injection\SetProperty */
final class SetPropertyTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::enhance
     */
    public function testEnhanceSuccessfully(): void
    {
        $instance = new StubClass(new StubService());

        $binding   = new Binding('string');
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn('injected-value');

        $enhancement = new SetProperty('property', $binding);
        $result      = $enhancement->enhance($instance, $container);

        static::assertSame($instance, $result);
        static::assertSame('injected-value', $instance->property);
    }

    /**
     * @covers ::__construct
     * @covers ::enhance
     */
    public function testEnhanceOnUntypedPropertyTreatsDependencyAsMixed(): void
    {
        // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint
        $instance = new class {
            public $untypedProperty; // phpcs:ignore
        };

        $binding   = new Binding('string');
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn('injected-value');

        $enhancement = new SetProperty('untypedProperty', $binding);
        $result      = $enhancement->enhance($instance, $container);

        static::assertSame($instance, $result);
        static::assertSame('injected-value', $instance->untypedProperty);
    }

    /**
     * @covers ::__construct
     * @covers ::enhance
     */
    public function testEnhanceWithPrivateProperty(): void
    {
        $instance = new class {
            private string $dependency;

            public function getDependency(): string
            {
                return $this->dependency;
            }
        };

        $binding   = new Binding('string');
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn('private-value');

        $enhancement = new SetProperty('dependency', $binding);
        $result      = $enhancement->enhance($instance, $container);

        static::assertSame($instance, $result);
        static::assertSame('private-value', $result->getDependency());
    }

    /**
     * @covers ::__construct
     * @covers ::enhance
     */
    public function testEnhanceWithNullableType(): void
    {
        $instance = new class {
            public string|null $dependency = null;
        };

        $binding   = new Binding('string');
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn('nullable-value');

        $enhancement = new SetProperty('dependency', $binding);
        $result      = $enhancement->enhance($instance, $container);

        static::assertSame('nullable-value', $instance->dependency);
    }

    /**
     * @covers ::__construct
     * @covers ::enhance
     */
    public function testEnhanceWithUnionType(): void
    {
        $instance = new class {
            public string|int $dependency;
        };

        $binding   = new Binding('string');
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn('union-value');

        $enhancement = new SetProperty('dependency', $binding);
        $result      = $enhancement->enhance($instance, $container);

        static::assertSame('union-value', $instance->dependency);
    }

    /**
     * @covers ::__construct
     * @covers ::enhance
     */
    public function testEnhanceWithUntypedProperty(): void
    {
        $instance = new class {
            public mixed $dependency;
        };

        $binding   = new Binding('string');
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn('any-value');

        $enhancement = new SetProperty('dependency', $binding);
        $result      = $enhancement->enhance($instance, $container);

        static::assertSame('any-value', $instance->dependency);
    }

    /**
     * @covers ::__construct
     * @covers ::enhance
     */
    public function testEnhanceFailsWhenInstanceIsNotObject(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('Expected value to be object');

        $binding     = new Binding('string');
        $container   = $this->createMock(Container::class);
        $enhancement = new SetProperty('dependency', $binding);

        $enhancement->enhance('not-an-object', $container);
    }

    /**
     * @covers ::__construct
     * @covers ::enhance
     */
    public function testEnhanceFailsWhenPropertyDoesNotExist(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('to have a property named "nonExistent"');

        $instance = new StubClass(new StubService());

        $binding     = new Binding('string');
        $container   = $this->createMock(Container::class);
        $enhancement = new SetProperty('nonExistent', $binding);

        $enhancement->enhance($instance, $container);
    }

    /**
     * @covers ::__construct
     * @covers ::enhance
     */
    public function testEnhanceFailsWhenTypeIsNotAssignable(): void
    {
        static::expectException(AssertionFailed::class);

        $instance = new StubClass(new StubService());

        $binding     = new Binding('int');
        $container   = $this->createMock(Container::class);
        $enhancement = new SetProperty('property', $binding);

        $enhancement->enhance($instance, $container);
    }

    /**
     * @covers ::__construct
     * @covers ::accept
     */
    public function testAcceptReturnsTrueWhenProviderHasPropertyAndIsInjectable(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn(StubClass::class);
        $provider->method('getCapabilities')->willReturn(
            HashSet::fromArray([Capability::INJECTABLE]),
        );

        $binding     = new Binding('string');
        $enhancement = new SetProperty('property', $binding);

        static::assertTrue($enhancement->accept($provider));
    }

    /**
     * @covers ::__construct
     * @covers ::accept
     */
    public function testAcceptReturnsTrueWhenProviderHasPropertyAndIsInterceptable(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn(StubClass::class);
        $provider->method('getCapabilities')->willReturn(
            HashSet::fromArray([Capability::INTERCEPTABLE]),
        );

        $binding     = new Binding('string');
        $enhancement = new SetProperty('property', $binding);

        static::assertTrue($enhancement->accept($provider));
    }

    /**
     * @covers ::__construct
     * @covers ::accept
     */
    public function testAcceptReturnsFalseWhenProviderDoesNotHaveProperty(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn(StubClass::class);
        $provider->method('getCapabilities')->willReturn(
            HashSet::fromArray([Capability::INJECTABLE]),
        );

        $binding     = new Binding('string');
        $enhancement = new SetProperty('property1', $binding);

        static::assertFalse($enhancement->accept($provider));
    }

    /**
     * @covers ::__construct
     * @covers ::getSlot
     */
    public function testGetSlotReturnsPropertyName(): void
    {
        $enhancement = new SetProperty('property', new Binding('string'));

        static::assertSame('property', $enhancement->getSlot());
    }

    /**
     * @param array<Capability> $capabilities
     *
     * @covers ::__construct
     * @covers ::accept
     * @dataProvider capabilitySetsProvider
     */
    public function testAcceptIgnoresProviderCapabilities(array $capabilities): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn(StubClass::class);
        $provider->method('getCapabilities')->willReturn(
            HashSet::fromArray($capabilities),
        );

        $binding     = new Binding('string');
        $enhancement = new SetProperty('property', $binding);

        static::assertTrue($enhancement->accept($provider));
    }

    /** @return array<string, array{array<Capability>}> */
    public static function capabilitySetsProvider(): array
    {
        return [
            'none' => [[]],
            'decorable only' => [[Capability::DECORABLE]],
            'injectable only' => [[Capability::INJECTABLE]],
            'all' => [[Capability::INJECTABLE, Capability::INTERCEPTABLE, Capability::DECORABLE]],
        ];
    }
}
