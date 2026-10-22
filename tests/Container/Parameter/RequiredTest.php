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
use Vivarium\Container\Parameter as ParameterContract;
use Vivarium\Container\Parameter\Optional;
use Vivarium\Container\Parameter\Required;
use Vivarium\Container\Parameter\Variadic;
use Vivarium\Container\Provider;
use Vivarium\Test\Container\Stub\StubBase;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubWithOptionalArg;

#[CoversClass(Required::class)]
final class RequiredTest extends TestCase
{
    public function testConstructor(): void
    {
        static::assertInstanceOf(
            Required::class,
            new Required('name', 'string', 'App\\Context'),
        );
    }

    #[DataProvider('provideInvalidConstructorArguments')]
    public function testConstructorException(string $name, string $type): void
    {
        static::expectException(AssertionFailed::class);

        new Required($name, $type, 'App\\Context');
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
        $parameter = new Required('name', 'string', 'App\\Context');

        static::assertSame('name', $parameter->getName());
    }

    public function testGetType(): void
    {
        $parameter = new Required('name', 'string', 'App\\Context');

        static::assertSame('string', $parameter->getType());
    }

    public function testGetArgument(): void
    {
        $parameter = new Required('name', 'string', 'App\\Context');

        static::assertInstanceOf(Provider::class, $parameter->getArgument());
    }

    public function testWithArgument(): void
    {
        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');

        $original = new Required('name', 'string', 'App\\Context');
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

        (new Required('name', 'int', 'App\\Context'))->withArgument($provider);
    }

    #[DataProvider('provideFreshAndBoundParameters')]
    public function testIsExplicit(Required $parameter, bool $expected): void
    {
        static::assertSame($expected, $parameter->isExplicit());
    }

    /** @return array<string, array{Required, bool}> */
    public static function provideFreshAndBoundParameters(): array
    {
        $provider = self::createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');

        return [
            'freshly constructed' => [new Required('name', 'string', 'App\\Context'), false],
            'explicit argument bound' => [
                (new Required('name', 'string', 'App\\Context'))->withArgument($provider),
                true,
            ],
        ];
    }

    #[DataProvider('provideReplacementPairs')]
    public function testCanReplace(Required $parameter, ParameterContract $base, bool $expected): void
    {
        static::assertSame($expected, $parameter->canReplace($base));
    }

    /** @return array<string, array{Required, ParameterContract, bool}> */
    public static function provideReplacementPairs(): array
    {
        $parameter = new Required('name', 'string', 'App\\Context');

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
                false,
            ],
            'base is Variadic' => [
                $parameter,
                new Variadic('name', 'string', 'App\\Context'),
                false,
            ],
            'same scalar type on both sides' => [
                new Required('name', 'int', 'App\\Context'),
                new Required('name', 'int', 'App\\Context'),
                true,
            ],
            'this parameter typed mixed, base typed anything' => [
                new Required('name', 'mixed', 'App\\Context'),
                new Required('name', 'int', 'App\\Context'),
                true,
            ],
            'this parameter typed with parent, base typed with subclass' => [
                new Required('name', StubBase::class, 'App\\Context'),
                new Required('name', StubClass::class, 'App\\Context'),
                true,
            ],
            'this parameter typed with subclass, base typed with parent' => [
                new Required('name', StubClass::class, 'App\\Context'),
                new Required('name', StubBase::class, 'App\\Context'),
                false,
            ],
            'unrelated scalar types' => [
                new Required('name', 'int', 'App\\Context'),
                new Required('name', 'string', 'App\\Context'),
                false,
            ],
        ];
    }

    public function testCanBeOmitted(): void
    {
        $parameter = new Required('name', 'string', 'App\\Context');

        static::assertFalse($parameter->canBeOmitted());
    }
}
