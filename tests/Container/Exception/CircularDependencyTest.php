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
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Binding;
use Vivarium\Container\Exception\CircularDependency;
use Vivarium\Container\Exception\ContainerException;
use Vivarium\Test\Container\Stub\StubService;
use Vivarium\Test\Container\Stub\StubWithNoArgs;

/** @coversDefaultClass \Vivarium\Container\Exception\CircularDependency */
final class CircularDependencyTest extends TestCase
{
    /**
     * @param list<Binding> $path
     *
     * @covers ::getPath
     * @covers ::__construct
     *
     * @dataProvider provideValidPaths
     */
    public function testGetPath(array $path): void
    {
        $exception = new CircularDependency($path);

        self::assertSame($path, $exception->getPath());
    }

    /**
     * @param list<Binding> $path
     *
     * @covers ::__construct
     *
     * @dataProvider provideInvalidPaths
     */
    public function testConstructorException(array $path): void
    {
        $this->expectException(AssertionFailed::class);

        new CircularDependency($path);
    }

    /** @return array<string, array{list<Binding>}> */
    public static function provideValidPaths(): array
    {
        $service           = new Binding(StubService::class);
        $noArgs            = new Binding(StubWithNoArgs::class);
        $taggedService     = new Binding(StubService::class, 'tag');
        $contextualService = new Binding(StubService::class, Binding::DEFAULT, 'App\\Orders');

        return [
            'direct self loop' => [[$service, new Binding(StubService::class)]],
            'loop of two' => [[$service, $noArgs, new Binding(StubService::class)]],
            'loop with prefix' => [[$service, $noArgs, $taggedService, new Binding(StubWithNoArgs::class)]],
            'tagged binding loop' => [[$service, $taggedService, new Binding(StubService::class, 'tag')]],
            'contextual binding loop' => [
                [$service, $contextualService, new Binding(StubService::class, Binding::DEFAULT, 'App\\Orders')],
            ],
        ];
    }

    /** @return array<string, array{list<Binding>}> */
    public static function provideInvalidPaths(): array
    {
        $service = new Binding(StubService::class);
        $noArgs  = new Binding(StubWithNoArgs::class);

        return [
            'empty path' => [[]],
            'single binding' => [[$service]],
            'two distinct bindings' => [[$service, $noArgs]],
            'three distinct bindings' => [[$service, $noArgs, new Binding(StubService::class, 'tag')]],
            'same type different context' => [
                [$service, new Binding(StubService::class, Binding::DEFAULT, 'App\\Orders')],
            ],
            'repeat only in the middle' => [[$service, $service, $noArgs]],
        ];
    }

    /**
     * @param list<Binding> $path
     *
     * @covers ::__construct
     *
     * @dataProvider provideMessages
     */
    public function testMessageBracketsTheLoopSegment(array $path, string $expected): void
    {
        $exception = new CircularDependency($path);

        self::assertSame($expected, $exception->getMessage());
    }

    /** @return array<string, array{list<Binding>, string}> */
    public static function provideMessages(): array
    {
        $service = StubService::class;
        $noArgs  = StubWithNoArgs::class;
        $arrow   = ' → ';

        return [
            'direct self loop' => [
                [new Binding($service), new Binding($service)],
                'Circular dependency: [' . $service . $arrow . $service . ']',
            ],
            'loop of two' => [
                [new Binding($service), new Binding($noArgs), new Binding($service)],
                'Circular dependency: [' . $service . $arrow . $noArgs . $arrow . $service . ']',
            ],
            'loop with prefix' => [
                [new Binding($service), new Binding($noArgs), new Binding($service, 'tag'), new Binding($noArgs)],
                'Circular dependency: ' . $service . $arrow
                    . '[' . $noArgs . $arrow . $service . ':tag' . $arrow . $noArgs . ']',
            ],
            'tagged loop' => [
                [new Binding($service, 'tag'), new Binding($noArgs), new Binding($service, 'tag')],
                'Circular dependency: [' . $service . ':tag' . $arrow . $noArgs . $arrow . $service . ':tag]',
            ],
            'contextual loop' => [
                [
                    new Binding($noArgs),
                    new Binding($service, Binding::DEFAULT, 'App\\Orders'),
                    new Binding($service, Binding::DEFAULT, 'App\\Orders'),
                ],
                'Circular dependency: ' . $noArgs . $arrow
                    . '[' . $service . ' (in App\\Orders)' . $arrow . $service . ' (in App\\Orders)]',
            ],
        ];
    }

    /**
     * @param class-string $expected
     *
     * @covers ::__construct
     *
     * @dataProvider provideExceptionTypes
     */
    public function testIsAContainerException(string $expected): void
    {
        $exception = new CircularDependency([new Binding(StubService::class), new Binding(StubService::class)]);

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
