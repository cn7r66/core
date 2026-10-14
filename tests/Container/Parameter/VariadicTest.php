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
use ReflectionMethod;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Container;
use Vivarium\Container\Parameter as ParameterContract;
use Vivarium\Container\Parameter\Optional;
use Vivarium\Container\Parameter\Required;
use Vivarium\Container\Parameter\Variadic;
use Vivarium\Container\Provider;
use Vivarium\Test\Container\Stub\StubWithOptionalArg;

/** @coversDefaultClass \Vivarium\Container\Parameter\Variadic */
final class VariadicTest extends TestCase
{
    /** @covers ::__construct */
    public function testConstructor(): void
    {
        static::assertInstanceOf(
            Variadic::class,
            new Variadic('name', 'string', 'App\\Context'),
        );
    }

    /**
     * @covers ::__construct
     * @dataProvider provideInvalidConstructorArguments
     */
    public function testConstructorException(string $name, string $type): void
    {
        static::expectException(AssertionFailed::class);

        new Variadic($name, $type, 'App\\Context');
    }

    /** @return Generator<string, array{string, string}> */
    public static function provideInvalidConstructorArguments(): Generator
    {
        yield 'empty name' => ['', 'string'];
        yield 'empty type' => ['name', ''];
    }

    /**
     * @covers ::__construct
     * @covers ::getName
     */
    public function testGetName(): void
    {
        $parameter = new Variadic('name', 'string', 'App\\Context');

        static::assertSame('name', $parameter->getName());
    }

    /**
     * @covers ::__construct
     * @covers ::getType
     */
    public function testGetType(): void
    {
        $parameter = new Variadic('name', 'string', 'App\\Context');

        static::assertSame('string', $parameter->getType());
    }

    /**
     * @covers ::__construct
     * @covers ::getArgument
     */
    public function testGetArgument(): void
    {
        $parameter = new Variadic('name', 'string', 'App\\Context');
        $argument  = $parameter->getArgument();

        $container = $this->createMock(Container::class);
        $container->method('has')->willReturn(false);

        static::assertInstanceOf(Provider::class, $argument);
        static::assertSame('array', $argument->getTarget());
        static::assertSame([], $argument->provide($container));
    }

    /**
     * @covers ::__construct
     * @covers ::withArgument
     */
    public function testWithArgument(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('array');

        $original = new Variadic('name', 'string', 'App\\Context');
        $modified = $original->withArgument($provider);

        static::assertNotSame($original, $modified);
        static::assertFalse($original->isExplicit());
        static::assertTrue($modified->isExplicit());
        static::assertSame($provider, $modified->getArgument());
    }

    /**
     * @covers ::__construct
     * @covers ::withArgument
     */
    public function testWithArgumentException(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('string');

        static::expectException(AssertionFailed::class);

        (new Variadic('name', 'string', 'App\\Context'))->withArgument($provider);
    }

    /**
     * @covers ::__construct
     * @covers ::withArgument
     * @covers ::isExplicit
     * @dataProvider provideIsExplicit
     */
    public function testIsExplicit(Variadic $parameter, bool $expected): void
    {
        static::assertSame($expected, $parameter->isExplicit());
    }

    /** @return Generator<string, array{Variadic, bool}> */
    public static function provideIsExplicit(): Generator
    {
        $provider = self::createStub(Provider::class);
        $provider->method('getTarget')->willReturn('array');

        yield 'freshly constructed' => [new Variadic('name', 'string', 'App\\Context'), false];
        yield 'explicit argument bound' => [
            (new Variadic('name', 'string', 'App\\Context'))->withArgument($provider),
            true,
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::canReplace
     * @dataProvider provideCanReplace
     */
    public function testCanReplace(Variadic $parameter, ParameterContract $base, bool $expected): void
    {
        static::assertSame($expected, $parameter->canReplace($base));
    }

    /** @return Generator<string, array{Variadic, ParameterContract, bool}> */
    public static function provideCanReplace(): Generator
    {
        $parameter = new Variadic('name', 'string', 'App\\Context');

        yield 'base is Required' => [
            $parameter,
            new Required('name', 'string', 'App\\Context'),
            true,
        ];

        $reflectionParameter = (new ReflectionMethod(StubWithOptionalArg::class, '__construct'))
            ->getParameters()[0];

        yield 'base is Optional' => [
            $parameter,
            new Optional('name', 'string', 'App\\Context', $reflectionParameter),
            true,
        ];

        yield 'base is Variadic' => [
            $parameter,
            new Variadic('name', 'string', 'App\\Context'),
            true,
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::canBeOmitted
     */
    public function testCanBeOmitted(): void
    {
        $parameter = new Variadic('name', 'string', 'App\\Context');

        static::assertTrue($parameter->canBeOmitted());
    }
}
