<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Parameter;

use Generator;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Container;
use Vivarium\Container\Parameter as ParameterContract;
use Vivarium\Container\Parameter\Optional;
use Vivarium\Container\Parameter\Required;
use Vivarium\Container\Parameter\Variadic;
use Vivarium\Container\Provider;
use Vivarium\Test\Container\Stub\StubBase;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubWithOptionalArg;
use Vivarium\Test\Container\Stub\StubWithRequiredStringArg;

/** @coversDefaultClass \Vivarium\Container\Parameter\Optional */
final class OptionalTest extends TestCase
{
    /** @covers ::__construct */
    public function testConstructor(): void
    {
        static::assertInstanceOf(
            Optional::class,
            new Optional('name', 'string', 'App\\Context', self::parameterWithDefault()),
        );
    }

    /**
     * @covers ::__construct
     * @dataProvider provideInvalidConstructorArguments
     */
    public function testConstructorException(
        string $name,
        string $type,
        string $context,
        ReflectionParameter $parameter,
    ): void {
        static::expectException(AssertionFailed::class);

        new Optional($name, $type, $context, $parameter);
    }

    /** @return Generator<string, array{string, string, string, ReflectionParameter}> */
    public static function provideInvalidConstructorArguments(): Generator
    {
        yield 'empty name' => ['', 'string', 'App\\Context', self::parameterWithDefault()];
        yield 'empty type' => ['name', '', 'App\\Context', self::parameterWithDefault()];
        yield 'parameter without default value' => ['name', 'string', 'App\\Context', self::parameterWithoutDefault()];
    }

    /**
     * @covers ::getName
     * @covers ::__construct
     */
    public function testGetName(): void
    {
        $parameter = new Optional('name', 'string', 'App\\Context', self::parameterWithDefault());

        static::assertSame('name', $parameter->getName());
    }

    /**
     * @covers ::getType
     * @covers ::__construct
     */
    public function testGetType(): void
    {
        $parameter = new Optional('name', 'int', 'App\\Context', self::parameterWithDefault());

        static::assertSame('int', $parameter->getType());
    }

    /**
     * @covers ::getArgument
     * @covers ::__construct
     * @dataProvider provideGetArgument
     */
    public function testGetArgument(ReflectionParameter $reflectionParameter, mixed $expectedDefault): void
    {
        $parameter = new Optional('name', 'string', 'App\\Context', $reflectionParameter);
        $argument  = $parameter->getArgument();

        static::assertInstanceOf(Provider::class, $argument);

        $containerWithoutBinding = $this->createMock(Container::class);
        $containerWithoutBinding->method('has')->willReturn(false);
        $containerWithoutBinding->expects(self::never())->method('get');

        static::assertSame($expectedDefault, $argument->provide($containerWithoutBinding));

        $containerWithBinding = $this->createMock(Container::class);
        $containerWithBinding->method('has')->willReturn(true);
        $containerWithBinding->method('get')->willReturn('container-value');

        static::assertSame('container-value', $argument->provide($containerWithBinding));
    }

    /** @return Generator<string, array{ReflectionParameter, mixed}> */
    public static function provideGetArgument(): Generator
    {
        yield 'string default' => [self::parameterWithDefault(), 'default'];

        $closure = static fn (string|null $value = null): string|null => $value;

        yield 'null default' => [(new ReflectionFunction($closure))->getParameters()[0], null];
    }

    /**
     * @covers ::withArgument
     * @covers ::__construct
     */
    public function testWithArgument(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('string');

        $original = new Optional('name', 'string', 'App\\Context', self::parameterWithDefault());
        $modified = $original->withArgument($provider);

        static::assertNotSame($original, $modified);
        static::assertFalse($original->isExplicit());
        static::assertTrue($modified->isExplicit());
        static::assertSame($provider, $modified->getArgument());
    }

    /**
     * @covers ::withArgument
     * @covers ::__construct
     */
    public function testWithArgumentException(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('string');

        static::expectException(AssertionFailed::class);

        (new Optional('name', 'int', 'App\\Context', self::parameterWithDefault()))->withArgument($provider);
    }

    /**
     * @covers ::isExplicit
     * @covers ::__construct
     * @covers ::withArgument
     * @dataProvider provideIsExplicit
     */
    public function testIsExplicit(Optional $parameter, bool $expected): void
    {
        static::assertSame($expected, $parameter->isExplicit());
    }

    /** @return Generator<string, array{Optional, bool}> */
    public static function provideIsExplicit(): Generator
    {
        $provider = self::createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');

        yield 'freshly constructed' => [
            new Optional('name', 'string', 'App\\Context', self::parameterWithDefault()),
            false,
        ];

        yield 'explicit argument bound' => [
            (new Optional('name', 'string', 'App\\Context', self::parameterWithDefault()))->withArgument($provider),
            true,
        ];
    }

    /**
     * @covers ::canReplace
     * @covers ::__construct
     * @dataProvider provideCanReplace
     */
    public function testCanReplace(Optional $parameter, ParameterContract $base, bool $expected): void
    {
        static::assertSame($expected, $parameter->canReplace($base));
    }

    /** @return Generator<string, array{Optional, ParameterContract, bool}> */
    public static function provideCanReplace(): Generator
    {
        $parameter = new Optional('name', 'string', 'App\\Context', self::parameterWithDefault());

        yield 'base is Required' => [
            $parameter,
            new Required('name', 'string', 'App\\Context'),
            true,
        ];

        yield 'base is Optional' => [
            $parameter,
            new Optional('name', 'string', 'App\\Context', self::parameterWithDefault()),
            true,
        ];

        yield 'base is Variadic' => [
            $parameter,
            new Variadic('name', 'string', 'App\\Context'),
            false,
        ];

        yield 'same scalar type on both sides' => [
            new Optional('name', 'int', 'App\\Context', self::parameterWithDefault()),
            new Required('name', 'int', 'App\\Context'),
            true,
        ];

        yield 'this parameter typed mixed, base typed anything' => [
            new Optional('name', 'mixed', 'App\\Context', self::parameterWithDefault()),
            new Required('name', 'int', 'App\\Context'),
            true,
        ];

        yield 'this parameter typed with parent, base typed with subclass' => [
            new Optional('name', StubBase::class, 'App\\Context', self::parameterWithDefault()),
            new Required('name', StubClass::class, 'App\\Context'),
            true,
        ];

        yield 'this parameter typed with subclass, base typed with parent' => [
            new Optional('name', StubClass::class, 'App\\Context', self::parameterWithDefault()),
            new Required('name', StubBase::class, 'App\\Context'),
            false,
        ];

        yield 'unrelated scalar types' => [
            new Optional('name', 'int', 'App\\Context', self::parameterWithDefault()),
            new Required('name', 'string', 'App\\Context'),
            false,
        ];
    }

    /**
     * @covers ::canBeOmitted
     * @covers ::__construct
     */
    public function testCanBeOmitted(): void
    {
        $parameter = new Optional('name', 'string', 'App\\Context', self::parameterWithDefault());

        static::assertTrue($parameter->canBeOmitted());
    }

    private static function parameterWithDefault(): ReflectionParameter
    {
        return (new ReflectionMethod(StubWithOptionalArg::class, '__construct'))->getParameters()[0];
    }

    private static function parameterWithoutDefault(): ReflectionParameter
    {
        return (new ReflectionMethod(StubWithRequiredStringArg::class, '__construct'))->getParameters()[0];
    }
}
