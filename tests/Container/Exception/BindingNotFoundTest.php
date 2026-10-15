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
use Vivarium\Container\Exception\BindingNotFound;
use Vivarium\Container\Exception\ContainerException;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubService;

/** @coversDefaultClass \Vivarium\Container\Exception\BindingNotFound */
final class BindingNotFoundTest extends TestCase
{
    /**
     * @covers ::getBinding
     * @covers ::__construct
     * @dataProvider provideBindings
     */
    public function testGetBinding(Binding $binding): void
    {
        $exception = new BindingNotFound($binding);

        self::assertSame($binding, $exception->getBinding());
    }

    /** @return array<string, array{Binding}> */
    public static function provideBindings(): array
    {
        return [
            'default tag and global context' => [new Binding(StubClass::class)],
            'custom tag' => [new Binding(StubClass::class, 'audit')],
            'custom context' => [new Binding(StubService::class, Binding::DEFAULT, 'App\\Orders')],
            'custom tag and context' => [new Binding(StubService::class, 'audit', 'App\\Orders')],
        ];
    }

    /** @return array<string, array{Binding, string}> */
    public static function provideBindingMessages(): array
    {
        $class   = StubClass::class;
        $service = StubService::class;

        return [
            'default tag and global context' => [
                new Binding($class),
                'No binding found for ' . $class . '.',
            ],
            'custom tag' => [
                new Binding($class, 'audit'),
                'No binding found for ' . $class . ':audit.',
            ],
            'custom context' => [
                new Binding($service, Binding::DEFAULT, 'App\\Orders'),
                'No binding found for ' . $service . ' (in App\\Orders).',
            ],
            'custom tag and context' => [
                new Binding($service, 'audit', 'App\\Orders'),
                'No binding found for ' . $service . ':audit (in App\\Orders).',
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @dataProvider provideBindingMessages
     */
    public function testMessageNamesTheMissingBinding(Binding $binding, string $message): void
    {
        $exception = new BindingNotFound($binding);

        self::assertSame($message, $exception->getMessage());
    }

    /**
     * @param class-string $expected
     *
     * @covers ::__construct
     * @dataProvider provideExceptionTypes
     */
    public function testIsAContainerException(string $expected): void
    {
        $exception = new BindingNotFound(new Binding(StubClass::class));

        self::assertInstanceOf($expected, $exception);
    }

    /** @return array<string, array{class-string}> */
    public static function provideExceptionTypes(): array
    {
        return [
            'runtime exception' => [RuntimeException::class],
            'container exception' => [ContainerException::class],
            'psr container exception' => [ContainerExceptionInterface::class],
        ];
    }
}
