<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Exception;

use Error;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use Throwable;
use Vivarium\Container\Binding;
use Vivarium\Container\Exception\ContainerException;
use Vivarium\Container\Exception\ResolutionFailed;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubService;

use function implode;

/** @coversDefaultClass \Vivarium\Container\Exception\ResolutionFailed */
final class ResolutionFailedTest extends TestCase
{
    /**
     * @param non-empty-list<Binding> $path
     *
     * @covers ::__construct
     * @dataProvider providePaths
     */
    public function testMessageRendersThePathAndThePreviousMessage(array $path, string $previousMessage): void
    {
        $rendered = [];

        foreach ($path as $binding) {
            $rendered[] = (string) $binding;
        }

        $exception = new ResolutionFailed($path, new RuntimeException($previousMessage));

        self::assertSame(
            'Resolution failed while resolving ' . implode(' → ', $rendered) . ': ' . $previousMessage,
            $exception->getMessage(),
        );
    }

    /**
     * @param non-empty-list<Binding> $path
     *
     * @covers ::getPath
     * @covers ::__construct
     * @dataProvider providePaths
     */
    public function testGetPath(array $path, string $previousMessage): void
    {
        $exception = new ResolutionFailed($path, new RuntimeException($previousMessage));

        self::assertSame($path, $exception->getPath());
    }

    /** @return array<string, array{non-empty-list<Binding>, string}> */
    public static function providePaths(): array
    {
        $classBinding   = new Binding(StubClass::class);
        $serviceBinding = new Binding(StubService::class);

        return [
            'single default binding' => [[$classBinding], 'boom'],
            'two default bindings' => [[$classBinding, $serviceBinding], 'boom'],
            'tagged binding' => [[$classBinding, new Binding(StubService::class, 'special')], 'failure'],
            'contextual binding' => [[new Binding(StubClass::class, Binding::DEFAULT, 'Some\\Ns')], 'failure'],
            'tagged contextual binding' => [
                [$classBinding, new Binding(StubService::class, 'special', 'Some\\Ns')],
                'nested failure',
            ],
            'empty previous message' => [[$classBinding, $serviceBinding], ''],
        ];
    }

    /**
     * @covers ::__construct
     * @dataProvider provideThrowables
     */
    public function testKeepsPreviousThrowableWithZeroCode(Throwable $previous): void
    {
        $exception = new ResolutionFailed([new Binding(StubClass::class)], $previous);

        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(0, $exception->getCode());
    }

    /** @return array<string, array{Throwable}> */
    public static function provideThrowables(): array
    {
        return [
            'runtime exception' => [new RuntimeException('boom')],
            'logic exception' => [new LogicException('bad')],
            'exception with code' => [new RuntimeException('coded', 42)],
            'error' => [new Error('fatal')],
        ];
    }

    /**
     * @param class-string $expected
     *
     * @covers ::__construct
     * @dataProvider provideExceptionTypes
     */
    public function testIsAContainerException(string $expected): void
    {
        $exception = new ResolutionFailed([new Binding(StubClass::class)], new RuntimeException('boom'));

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
