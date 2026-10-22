<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Binding;

use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Binding;
use Vivarium\Container\Binding\ProviderBinder;
use Vivarium\Container\Container;
use Vivarium\Container\Injector;
use Vivarium\Container\Provider;
use Vivarium\Container\Provider\ClassConstant;
use Vivarium\Container\Provider\Constant;
use Vivarium\Container\Provider\Constructor;
use Vivarium\Container\Provider\ContainerCall;
use Vivarium\Container\Provider\Enum;
use Vivarium\Container\Provider\Factory;
use Vivarium\Container\Provider\Fun;
use Vivarium\Container\Provider\Instance;
use Vivarium\Container\Provider\StaticFactory;
use Vivarium\Container\Registry\EagerRegistry;
use Vivarium\Container\Scope;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubFactory;
use Vivarium\Test\Container\Stub\StubStaticFactory;
use Vivarium\Test\Container\Stub\StubWithNoArgs;

#[CoversClass(ProviderBinder::class)]
final class ProviderBinderTest extends TestCase
{
    private function makeBinder(string $type): ProviderBinder
    {
        return new ProviderBinder(
            new Binding($type),
            static function (Binding $binding, Provider $provider): Provider {
                return $provider;
            },
        );
    }

    public function testToProviderForwardsProviderToCreateAndReturnsResult(): void
    {
        $provider = $this->createStub(Provider::class);
        $captured = null;

        $binder = new ProviderBinder(
            new Binding(StubWithNoArgs::class),
            static function (Binding $binding, Provider $forwarded) use (&$captured): Provider {
                $captured = $forwarded;

                return $forwarded;
            },
        );

        $result = $binder->toProvider($provider);

        static::assertSame($provider, $captured);
        static::assertSame($provider, $result);
    }

    public function testToConstructorCreatesConstructorProvider(): void
    {
        $provider = $this->makeBinder(StubWithNoArgs::class)->toConstructor();

        static::assertInstanceOf(Constructor::class, $provider);
    }

    public function testToFactoryCreatesFactoryProvider(): void
    {
        $provider = $this->makeBinder(StubClass::class)
            ->toFactory(StubFactory::class)
            ->method('create');

        static::assertInstanceOf(Factory::class, $provider);
    }

    public function testToStaticFactoryCreatesStaticFactoryProvider(): void
    {
        $provider = $this->makeBinder(StubClass::class)
            ->toStaticFactory(StubStaticFactory::class)
            ->method('create');

        static::assertInstanceOf(StaticFactory::class, $provider);
    }

    public function testToInstanceCreatesInstanceProvider(): void
    {
        $instance = new StubWithNoArgs();
        $provider = $this->makeBinder(StubWithNoArgs::class)->toInstance($instance);

        static::assertInstanceOf(Instance::class, $provider);
    }

    public function testToConstantCreatesConstantProvider(): void
    {
        $provider = $this->makeBinder('int')->toConstant('PHP_INT_MAX');

        static::assertInstanceOf(Constant::class, $provider);
    }

    public function testToEnumCreatesEnumProvider(): void
    {
        $provider = $this->makeBinder(Scope::class)->toEnum(Scope::class, 'SERVICE');

        static::assertInstanceOf(Enum::class, $provider);
    }

    public function testToClassConstantCreatesClassConstantProvider(): void
    {
        $provider = $this->makeBinder('int')
            ->toClassConstant(StubClass::class, 'INT_CONSTANT');

        static::assertInstanceOf(ClassConstant::class, $provider);
    }

    public function testToCreatesContainerCallProvider(): void
    {
        $provider = $this->makeBinder(StubWithNoArgs::class)
            ->to(StubWithNoArgs::class);

        static::assertInstanceOf(ContainerCall::class, $provider);
    }

    #[DataProvider('provideAssignableInstances')]
    public function testToInstanceProvidesTheGivenValue(string $type, mixed $value): void
    {
        $provider = $this->makeBinder($type)->toInstance($value);

        static::assertInstanceOf(Instance::class, $provider);
        static::assertSame($value, $provider->provide($this->createStub(Container::class)));
    }

    /** @return array<string, array{string, mixed}> */
    public static function provideAssignableInstances(): array
    {
        return [
            'class' => [StubWithNoArgs::class, new StubWithNoArgs()],
            'callable from closure' => [
                'callable',
                static function (): void {
                },
            ],
            'int' => ['int', 5432],
            'string' => ['string', 'x'],
            'closure' => [
                Closure::class,
                static function (): void {
                },
            ],
        ];
    }

    #[DataProvider('provideRejectedInstances')]
    public function testToInstanceRejectsNonAssignableValue(string $type, mixed $value): void
    {
        $binder = $this->makeBinder($type);

        $this->expectException(AssertionFailed::class);

        $binder->toInstance($value);
    }

    /** @return array<string, array{string, mixed}> */
    public static function provideRejectedInstances(): array
    {
        return [
            'null for class' => [StubWithNoArgs::class, null],
            'null for int' => ['int', null],
            'string for int' => ['int', 'nope'],
            'unrelated object for class' => [StubWithNoArgs::class, new stdClass()],
        ];
    }

    public function testToFunctionCreatesFunProviderThatCallsTheFunction(): void
    {
        $provider = $this->makeBinder(StubWithNoArgs::class)->toFunction(
            static function (StubWithNoArgs $dependency): StubWithNoArgs {
                return $dependency;
            },
        );

        static::assertInstanceOf(Fun::class, $provider);
        static::assertInstanceOf(
            StubWithNoArgs::class,
            $provider->provide(new Injector(new EagerRegistry())),
        );
    }

    public function testToFunctionUsesFunReturnedByConfigure(): void
    {
        $replacement = new Fun(static function (): string {
            return 'configured';
        });

        $provider = $this->makeBinder('string')->toFunction(
            static function (): string {
                return 'original';
            },
            static function (Fun $fun) use ($replacement): Fun {
                return $replacement;
            },
        );

        static::assertSame($replacement, $provider);
        static::assertSame('configured', $provider->provide(new Injector(new EagerRegistry())));
    }
}
