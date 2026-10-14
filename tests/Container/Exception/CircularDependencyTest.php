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

    /** @return iterable<string, array{list<Binding>}> */
    public static function provideValidPaths(): iterable
    {
        $a = new Binding(StubService::class);
        $b = new Binding(StubWithNoArgs::class);
        $c = new Binding(StubService::class, 'tag');
        $d = new Binding(StubService::class, Binding::DEFAULT, 'App\\Orders');

        yield 'direct self loop' => [[$a, new Binding(StubService::class)]];
        yield 'loop of two' => [[$a, $b, new Binding(StubService::class)]];
        yield 'loop with prefix' => [[$a, $b, $c, new Binding(StubWithNoArgs::class)]];
        yield 'tagged binding loop' => [[$a, $c, new Binding(StubService::class, 'tag')]];
        yield 'contextual binding loop' => [[$a, $d, new Binding(StubService::class, Binding::DEFAULT, 'App\\Orders')]];
    }

    /** @return iterable<string, array{list<Binding>}> */
    public static function provideInvalidPaths(): iterable
    {
        $a = new Binding(StubService::class);
        $b = new Binding(StubWithNoArgs::class);

        yield 'empty path' => [[]];
        yield 'single binding' => [[$a]];
        yield 'two distinct bindings' => [[$a, $b]];
        yield 'three distinct bindings' => [[$a, $b, new Binding(StubService::class, 'tag')]];
        yield 'same type different context' => [[$a, new Binding(StubService::class, Binding::DEFAULT, 'App\\Orders')]];
        yield 'repeat only in the middle' => [[$a, $a, $b]];
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

    /** @return iterable<string, array{list<Binding>, string}> */
    public static function provideMessages(): iterable
    {
        $service = StubService::class;
        $noArgs  = StubWithNoArgs::class;
        $arrow   = ' → ';

        yield 'direct self loop' => [
            [new Binding($service), new Binding($service)],
            'Circular dependency: [' . $service . $arrow . $service . ']',
        ];

        yield 'loop of two' => [
            [new Binding($service), new Binding($noArgs), new Binding($service)],
            'Circular dependency: [' . $service . $arrow . $noArgs . $arrow . $service . ']',
        ];

        yield 'loop with prefix' => [
            [new Binding($service), new Binding($noArgs), new Binding($service, 'tag'), new Binding($noArgs)],
            'Circular dependency: ' . $service . $arrow
                . '[' . $noArgs . $arrow . $service . ':tag' . $arrow . $noArgs . ']',
        ];

        yield 'tagged loop' => [
            [new Binding($service, 'tag'), new Binding($noArgs), new Binding($service, 'tag')],
            'Circular dependency: [' . $service . ':tag' . $arrow . $noArgs . $arrow . $service . ':tag]',
        ];

        yield 'contextual loop' => [
            [
                new Binding($noArgs),
                new Binding($service, Binding::DEFAULT, 'App\\Orders'),
                new Binding($service, Binding::DEFAULT, 'App\\Orders'),
            ],
            'Circular dependency: ' . $noArgs . $arrow
                . '[' . $service . ' (in App\\Orders)' . $arrow . $service . ' (in App\\Orders)]',
        ];
    }

    /** @covers ::__construct */
    public function testIsAContainerException(): void
    {
        $exception = new CircularDependency([new Binding(StubService::class), new Binding(StubService::class)]);

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertInstanceOf(ContainerException::class, $exception);
        self::assertInstanceOf(ContainerExceptionInterface::class, $exception);
    }
}
