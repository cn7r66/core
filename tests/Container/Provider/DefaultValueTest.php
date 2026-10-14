<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Provider;

use Closure;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionParameter;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Provider\DefaultValue;
use Vivarium\Test\Container\Stub\StubDefaultValueSource;
use Vivarium\Test\Container\Stub\StubDefaultValueTarget;

/** @coversDefaultClass \Vivarium\Container\Provider\DefaultValue */
final class DefaultValueTest extends TestCase
{
    /** @covers ::__construct */
    public function testConstructor(): void
    {
        self::expectNotToPerformAssertions();

        new DefaultValue(self::parameterOf(
            static function (string $value = 'default'): void {
            },
        ));
    }

    /** @covers ::__construct */
    public function testConstructorException(): void
    {
        self::expectException(AssertionFailed::class);

        new DefaultValue(self::parameterOf(
            static function (string $value): void {
            },
        ));
    }

    /**
     * @covers ::provide
     * @covers ::__construct
     * @dataProvider provideProvideCases
     */
    public function testProvide(callable $parameterFactory, callable $assertion): void
    {
        $provider  = new DefaultValue($parameterFactory());
        $container = $this->createMock(Container::class);

        $assertion($provider, $container);
    }

    /**
     * @covers ::getTarget
     * @covers ::__construct
     * @dataProvider provideGetTargetCases
     */
    public function testGetTarget(string $expected, callable $parameterFactory): void
    {
        $provider = new DefaultValue($parameterFactory());

        self::assertSame($expected, $provider->getTarget());
    }

    /**
     * @covers ::getCapabilities
     * @covers ::__construct
     */
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

    /** @return iterable<string, array{0: callable(): ReflectionParameter, 1: callable(DefaultValue, Container): void}> */
    public static function provideProvideCases(): iterable
    {
        yield 'scalar default' => [
            static fn (): ReflectionParameter => self::parameterOf(
                static function (string $value = 'expected'): void {
                },
            ),
            static function (DefaultValue $provider, Container $container): void {
                self::assertSame('expected', $provider->provide($container));
                self::assertSame('expected', $provider->provide($container));
            },
        ];

        yield 'object-creating default' => [
            static fn (): ReflectionParameter => self::parameterOf(
                static function (StubDefaultValueTarget $value = new StubDefaultValueSource()): void {
                },
            ),
            static function (DefaultValue $provider, Container $container): void {
                $first  = $provider->provide($container);
                $second = $provider->provide($container);

                self::assertInstanceOf(StubDefaultValueSource::class, $first);
                self::assertInstanceOf(StubDefaultValueSource::class, $second);
                self::assertNotSame($first, $second);
            },
        ];
    }

    /** @return iterable<string, array{0: string, 1: callable(): ReflectionParameter}> */
    public static function provideGetTargetCases(): iterable
    {
        yield 'typed parameter with object-creating default from subclass' => [
            StubDefaultValueTarget::class,
            static fn (): ReflectionParameter => self::parameterOf(
                static function (StubDefaultValueTarget $value = new StubDefaultValueSource()): void {
                },
            ),
        ];

        yield 'untyped parameter' => [
            'mixed',
            static fn (): ReflectionParameter => self::parameterOf(
                // phpcs:ignore
                static function ($value = 'default'): void {
                },
            ),
        ];

        yield 'nullable typed parameter' => [
            StubDefaultValueTarget::class . '|null',
            static fn (): ReflectionParameter => self::parameterOf(
                static function (StubDefaultValueTarget|null $value = null): void {
                },
            ),
        ];
    }

    private static function parameterOf(Closure $closure): ReflectionParameter
    {
        return (new ReflectionFunction($closure))->getParameters()[0];
    }
}
