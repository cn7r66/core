<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Parameter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

#[CoversClass(Variadic::class)]
final class VariadicTest extends TestCase
{
    public function testConstructor(): void
    {
        static::assertInstanceOf(
            Variadic::class,
            new Variadic('name', 'string', 'App\\Context'),
        );
    }

    #[DataProvider('provideInvalidConstructorArguments')]
    public function testConstructorException(string $name, string $type): void
    {
        static::expectException(AssertionFailed::class);

        new Variadic($name, $type, 'App\\Context');
    }

    /** @return array<string, array{string, string}> */
    public static function provideInvalidConstructorArguments(): array
    {
        return [
            'empty name' => ['', 'string'],
            'empty type' => ['name', ''],
        ];
    }

    public function testGetName(): void
    {
        $parameter = new Variadic('name', 'string', 'App\\Context');

        static::assertSame('name', $parameter->getName());
    }

    public function testGetType(): void
    {
        $parameter = new Variadic('name', 'string', 'App\\Context');

        static::assertSame('string', $parameter->getType());
    }

    public function testGetArgument(): void
    {
        $parameter = new Variadic('name', 'string', 'App\\Context');
        $argument  = $parameter->getArgument();

        $container = $this->createStub(Container::class);
        $container->method('has')->willReturn(false);

        static::assertInstanceOf(Provider::class, $argument);
        static::assertSame('array', $argument->getTarget());
        static::assertSame([], $argument->provide($container));
    }

    public function testWithArgument(): void
    {
        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn('array');

        $original = new Variadic('name', 'string', 'App\\Context');
        $modified = $original->withArgument($provider);

        static::assertNotSame($original, $modified);
        static::assertFalse($original->isExplicit());
        static::assertTrue($modified->isExplicit());
        static::assertSame($provider, $modified->getArgument());
    }

    public function testWithArgumentException(): void
    {
        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');

        static::expectException(AssertionFailed::class);

        (new Variadic('name', 'string', 'App\\Context'))->withArgument($provider);
    }

    #[DataProvider('provideFreshAndBoundParameters')]
    public function testIsExplicit(Variadic $parameter, bool $expected): void
    {
        static::assertSame($expected, $parameter->isExplicit());
    }

    /** @return array<string, array{Variadic, bool}> */
    public static function provideFreshAndBoundParameters(): array
    {
        $provider = self::createStub(Provider::class);
        $provider->method('getTarget')->willReturn('array');

        return [
            'freshly constructed' => [new Variadic('name', 'string', 'App\\Context'), false],
            'explicit argument bound' => [
                (new Variadic('name', 'string', 'App\\Context'))->withArgument($provider),
                true,
            ],
        ];
    }

    #[DataProvider('provideReplacementPairs')]
    public function testCanReplace(Variadic $parameter, ParameterContract $base, bool $expected): void
    {
        static::assertSame($expected, $parameter->canReplace($base));
    }

    /** @return array<string, array{Variadic, ParameterContract, bool}> */
    public static function provideReplacementPairs(): array
    {
        $parameter = new Variadic('name', 'string', 'App\\Context');

        $reflectionParameter = (new ReflectionMethod(StubWithOptionalArg::class, '__construct'))
            ->getParameters()[0];

        return [
            'base is Required' => [
                $parameter,
                new Required('name', 'string', 'App\\Context'),
                true,
            ],
            'base is Optional' => [
                $parameter,
                new Optional('name', 'string', 'App\\Context', $reflectionParameter),
                true,
            ],
            'base is Variadic' => [
                $parameter,
                new Variadic('name', 'string', 'App\\Context'),
                true,
            ],
        ];
    }

    public function testCanBeOmitted(): void
    {
        $parameter = new Variadic('name', 'string', 'App\\Context');

        static::assertTrue($parameter->canBeOmitted());
    }
}
