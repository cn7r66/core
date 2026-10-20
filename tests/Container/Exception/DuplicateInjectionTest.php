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
use Vivarium\Container\Exception\DuplicateInjection;
use Vivarium\Test\Container\Stub\StubClass;

/** @coversDefaultClass \Vivarium\Container\Exception\DuplicateInjection */
final class DuplicateInjectionTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getBinding
     * @covers ::getSlot
     * @dataProvider provideSlots
     */
    public function testExposesBindingAndSlot(string $slot): void
    {
        $binding   = new Binding(StubClass::class);
        $exception = new DuplicateInjection($binding, $slot);

        static::assertSame($binding, $exception->getBinding());
        static::assertSame($slot, $exception->getSlot());
    }

    /**
     * @covers ::__construct
     * @dataProvider provideBindingsWithSlots
     */
    public function testMessageSuggestsAMoreSpecificType(Binding $binding, string $description, string $slot): void
    {
        $exception = new DuplicateInjection($binding, $slot);

        static::assertSame(
            'Injection for "' . $slot . '" is already registered on ' . $description
                . '. Register it on a more specific type to override.',
            $exception->getMessage(),
        );
    }

    /**
     * @covers ::__construct
     * @dataProvider provideExceptionTypes
     */
    public function testIsAContainerException(string $type): void
    {
        static::assertInstanceOf($type, new DuplicateInjection(new Binding(StubClass::class), 'configure'));
    }

    /** @return array<string, array{string}> */
    public static function provideSlots(): array
    {
        return [
            'method slot' => ['configure'],
            'property slot' => ['$logger'],
        ];
    }

    /** @return array<string, array{Binding, string, string}> */
    public static function provideBindingsWithSlots(): array
    {
        return [
            'method slot, plain binding' => [new Binding(StubClass::class), StubClass::class, 'configure'],
            'property slot, tagged and contextual binding' => [
                new Binding(StubClass::class, 'main', 'App\Domain'),
                StubClass::class . ':main (in App\Domain)',
                '$logger',
            ],
        ];
    }

    /** @return array<string, array{string}> */
    public static function provideExceptionTypes(): array
    {
        return [
            'runtime exception' => [RuntimeException::class],
            'container exception' => [ContainerException::class],
            'psr container exception' => [ContainerExceptionInterface::class],
        ];
    }
}
