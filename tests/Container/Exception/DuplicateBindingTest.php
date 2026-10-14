<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Exception;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use Vivarium\Container\Binding;
use Vivarium\Container\Exception\ContainerException;
use Vivarium\Container\Exception\DuplicateBinding;
use Vivarium\Test\Container\Stub\StubClass;

/** @coversDefaultClass \Vivarium\Container\Exception\DuplicateBinding */
final class DuplicateBindingTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getBinding
     */
    public function testExposesTheBinding(): void
    {
        $binding   = new Binding(StubClass::class);
        $exception = new DuplicateBinding($binding);

        static::assertSame($binding, $exception->getBinding());
    }

    /**
     * @covers ::__construct
     * @dataProvider provideBindings
     */
    public function testMessageSuggestsExtend(Binding $binding, string $description): void
    {
        $exception = new DuplicateBinding($binding);

        static::assertSame(
            'Binding ' . $description . ' is already registered. Use extend() to replace it.',
            $exception->getMessage(),
        );
    }

    /**
     * @covers ::__construct
     * @dataProvider provideExceptionTypes
     */
    public function testIsAContainerException(string $type): void
    {
        static::assertInstanceOf($type, new DuplicateBinding(new Binding(StubClass::class)));
    }

    /** @return iterable<string, array{Binding, string}> */
    public static function provideBindings(): iterable
    {
        yield 'plain' => [new Binding(StubClass::class), StubClass::class];
        yield 'tagged and contextual' => [
            new Binding(StubClass::class, 'main', 'App\Domain'),
            StubClass::class . ':main (in App\Domain)',
        ];
    }

    /** @return iterable<string, array{string}> */
    public static function provideExceptionTypes(): iterable
    {
        yield 'runtime exception' => [RuntimeException::class];
        yield 'container exception' => [ContainerException::class];
        yield 'psr container exception' => [ContainerExceptionInterface::class];
    }
}
