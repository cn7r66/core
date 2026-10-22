<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use Vivarium\Container\Binding;
use Vivarium\Container\Exception\ContainerException;
use Vivarium\Container\Exception\DuplicateBinding;
use Vivarium\Test\Container\Stub\StubClass;

#[CoversClass(DuplicateBinding::class)]
final class DuplicateBindingTest extends TestCase
{
    public function testExposesTheBinding(): void
    {
        $binding   = new Binding(StubClass::class);
        $exception = new DuplicateBinding($binding);

        static::assertSame($binding, $exception->getBinding());
    }

    #[DataProvider('provideBindings')]
    public function testMessageSuggestsExtend(Binding $binding, string $description): void
    {
        $exception = new DuplicateBinding($binding);

        static::assertSame(
            'Binding ' . $description . ' is already registered. Use extend() to replace it.',
            $exception->getMessage(),
        );
    }

    #[DataProvider('provideExceptionTypes')]
    public function testIsAContainerException(string $type): void
    {
        static::assertInstanceOf($type, new DuplicateBinding(new Binding(StubClass::class)));
    }

    /** @return array<string, array{Binding, string}> */
    public static function provideBindings(): array
    {
        return [
            'plain' => [new Binding(StubClass::class), StubClass::class],
            'tagged and contextual' => [
                new Binding(StubClass::class, 'main', 'App\Domain'),
                StubClass::class . ':main (in App\Domain)',
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
