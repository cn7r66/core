<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Provider;

use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionParameter;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Provider\DefaultValue;
use Vivarium\Test\Container\Stub\StubDefaultValueSource;
use Vivarium\Test\Container\Stub\StubDefaultValueTarget;

#[CoversClass(DefaultValue::class)]
final class DefaultValueTest extends TestCase
{
    public function testConstructor(): void
    {
        self::expectNotToPerformAssertions();

        new DefaultValue(self::parameterOf(
            static function (string $value = 'default'): void {
            },
        ));
    }

    public function testConstructorException(): void
    {
        self::expectException(AssertionFailed::class);

        new DefaultValue(self::parameterOf(
            static function (string $value): void {
            },
        ));
    }

    public function testProvideReturnsScalarDefaultOnEveryCall(): void
    {
        $provider  = new DefaultValue(self::parameterOf(
            static function (string $value = 'expected'): void {
            },
        ));
        $container = $this->createStub(Container::class);

        self::assertSame('expected', $provider->provide($container));
        self::assertSame('expected', $provider->provide($container));
    }

    public function testProvideBuildsFreshObjectDefaultOnEveryCall(): void
    {
        $provider  = new DefaultValue(self::parameterOf(
            static function (StubDefaultValueTarget $value = new StubDefaultValueSource()): void {
            },
        ));
        $container = $this->createStub(Container::class);

        $first  = $provider->provide($container);
        $second = $provider->provide($container);

        self::assertInstanceOf(StubDefaultValueSource::class, $first);
        self::assertInstanceOf(StubDefaultValueSource::class, $second);
        self::assertNotSame($first, $second);
    }

    #[DataProvider('provideDefaultedParameterTargets')]
    public function testGetTarget(string $expected, ReflectionParameter $parameter): void
    {
        $provider = new DefaultValue($parameter);

        self::assertSame($expected, $provider->getTarget());
    }

    public function testGetCapabilities(): void
    {
        $provider = new DefaultValue(self::parameterOf(
            static function (string $value = 'default'): void {
            },
        ));

        $capabilities = $provider->getCapabilities();

        self::assertTrue($capabilities->contains(Capability::INTERCEPTABLE));
        self::assertTrue($capabilities->contains(Capability::DECORABLE));
        self::assertCount(2, $capabilities);
    }

    /** @return array<string, array{string, ReflectionParameter}> */
    public static function provideDefaultedParameterTargets(): array
    {
        return [
            'typed parameter with object-creating default from subclass' => [
                StubDefaultValueTarget::class,
                self::parameterOf(
                    static function (StubDefaultValueTarget $value = new StubDefaultValueSource()): void {
                    },
                ),
            ],
            'untyped parameter' => [
                'mixed',
                self::parameterOf(
                    // phpcs:ignore
                    static function ($value = 'default'): void {
                    },
                ),
            ],
            'nullable typed parameter' => [
                StubDefaultValueTarget::class . '|null',
                self::parameterOf(
                    static function (StubDefaultValueTarget|null $value = null): void {
                    },
                ),
            ],
        ];
    }

    private static function parameterOf(Closure $closure): ReflectionParameter
    {
        return (new ReflectionFunction($closure))->getParameters()[0];
    }
}
