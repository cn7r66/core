<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Injection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

#[CoversClass(SetProperty::class)]
final class SetPropertyTest extends TestCase
{
    #[DataProvider('providePublicPropertiesByDeclaredType')]
    public function testEnhanceAssignsDependencyToPublicProperty(
        object $instance,
        string $property,
        string $value,
    ): void {
        $binding   = new Binding('string');
        $container = $this->createStub(Container::class);
        $container->method('get')->willReturn($value);

        $enhancement = new SetProperty($property, $binding);
        $result      = $enhancement->enhance($instance, $container);

        static::assertSame($instance, $result);
        static::assertSame($value, $instance->$property);
    }

    /** @return array<string, array{object, string, string}> */
    public static function providePublicPropertiesByDeclaredType(): array
    {
        return [
            'typed property' => [new StubClass(new StubService()), 'property', 'injected-value'],
            'untyped property' => [
                // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint
                new class {
                    public $untypedProperty; // phpcs:ignore
                },
                'untypedProperty',
                'injected-value',
            ],
            'nullable type' => [
                new class {
                    public string|null $dependency = null;
                },
                'dependency',
                'nullable-value',
            ],
            'union type' => [
                new class {
                    public string|int $dependency;
                },
                'dependency',
                'union-value',
            ],
            'mixed type' => [
                new class {
                    public mixed $dependency;
                },
                'dependency',
                'any-value',
            ],
        ];
    }

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
        $container = $this->createStub(Container::class);
        $container->method('get')->willReturn('private-value');

        $enhancement = new SetProperty('dependency', $binding);
        $result      = $enhancement->enhance($instance, $container);

        static::assertSame($instance, $result);
        static::assertSame('private-value', $result->getDependency());
    }

    public function testEnhanceFailsWhenInstanceIsNotObject(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('Expected value to be object');

        $binding     = new Binding('string');
        $container   = $this->createStub(Container::class);
        $enhancement = new SetProperty('dependency', $binding);

        $enhancement->enhance('not-an-object', $container);
    }

    public function testEnhanceFailsWhenPropertyDoesNotExist(): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage('to have a property named "nonExistent"');

        $instance = new StubClass(new StubService());

        $binding     = new Binding('string');
        $container   = $this->createStub(Container::class);
        $enhancement = new SetProperty('nonExistent', $binding);

        $enhancement->enhance($instance, $container);
    }

    public function testEnhanceFailsWhenTypeIsNotAssignable(): void
    {
        static::expectException(AssertionFailed::class);

        $instance = new StubClass(new StubService());

        $binding     = new Binding('int');
        $container   = $this->createStub(Container::class);
        $enhancement = new SetProperty('property', $binding);

        $enhancement->enhance($instance, $container);
    }

    public function testAcceptReturnsFalseWhenProviderDoesNotHaveProperty(): void
    {
        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn(StubClass::class);
        $provider->method('getCapabilities')->willReturn(
            HashSet::fromArray([Capability::INJECTABLE]),
        );

        $binding     = new Binding('string');
        $enhancement = new SetProperty('property1', $binding);

        static::assertFalse($enhancement->accept($provider));
    }

    public function testGetSlotIsPropertyNameWithDollarSign(): void
    {
        $enhancement = new SetProperty('property', new Binding('string'));

        static::assertSame('$property', $enhancement->getSlot());
    }

    /** @param array<Capability> $capabilities */
    #[DataProvider('provideCapabilitySets')]
    public function testAcceptIgnoresProviderCapabilities(array $capabilities): void
    {
        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn(StubClass::class);
        $provider->method('getCapabilities')->willReturn(
            HashSet::fromArray($capabilities),
        );

        $binding     = new Binding('string');
        $enhancement = new SetProperty('property', $binding);

        static::assertTrue($enhancement->accept($provider));
    }

    /** @return array<string, array{array<Capability>}> */
    public static function provideCapabilitySets(): array
    {
        return [
            'none' => [[]],
            'decorable only' => [[Capability::DECORABLE]],
            'injectable only' => [[Capability::INJECTABLE]],
            'interceptable only' => [[Capability::INTERCEPTABLE]],
            'all' => [[Capability::INJECTABLE, Capability::INTERCEPTABLE, Capability::DECORABLE]],
        ];
    }
}
