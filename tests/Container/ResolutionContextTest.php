<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container;

use ArrayObject;
use Closure;
use DateTime;
use Exception;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Throwable;
use Vivarium\Container\Binding;
use Vivarium\Container\Container;
use Vivarium\Container\Exception\CircularDependency;
use Vivarium\Container\Exception\ResolutionFailed;
use Vivarium\Container\ResolutionContext;

/** @coversDefaultClass \Vivarium\Container\ResolutionContext */
final class ResolutionContextTest extends TestCase
{
    /**
     * @param array<string, list<string>> $graph
     * @param list<Binding>               $path
     */
    private function graphContext(array $graph, array $path = []): ResolutionContext
    {
        $resolve = static function (Binding $binding, Container $context) use ($graph): string {
            foreach ($graph[$binding->getType()] ?? [] as $child) {
                $context->get($child);
            }

            return $binding->getType();
        };

        return new ResolutionContext(
            static fn (Binding $binding): Binding => $binding,
            $resolve,
            $this->createMock(Container::class),
            $path,
        );
    }

    /**
     * @param list<Binding> $path
     *
     * @return list<string>
     */
    private static function render(array $path): array
    {
        $rendered = [];

        foreach ($path as $binding) {
            $rendered[] = (string) $binding;
        }

        return $rendered;
    }

    /** @covers ::__construct */
    public function testConstructedContextIsAContainer(): void
    {
        $context = new ResolutionContext(
            static fn (Binding $binding): Binding => $binding,
            static fn (Binding $binding, Container $context): mixed => null,
            $this->createMock(Container::class),
        );

        self::assertInstanceOf(Container::class, $context);
    }

    /**
     * @covers ::get
     * @covers ::__construct
     */
    public function testStringRequestIsLocatedAsDefaultTagGlobalContextBinding(): void
    {
        $located = [];
        $context = new ResolutionContext(
            static function (Binding $binding) use (&$located): Binding {
                $located[] = $binding;

                return $binding;
            },
            static fn (Binding $binding, Container $context): mixed => null,
            $this->createMock(Container::class),
        );

        $context->get(stdClass::class);

        self::assertCount(1, $located);
        self::assertTrue($located[0]->equals(new Binding(stdClass::class, Binding::DEFAULT, Binding::GLOBAL)));
    }

    /**
     * @covers ::get
     * @covers ::__construct
     */
    public function testBindingRequestIsLocatedAsIs(): void
    {
        $request = new Binding(stdClass::class, 'audit', 'App\Orders');
        $located = null;
        $context = new ResolutionContext(
            static function (Binding $binding) use (&$located): Binding {
                $located = $binding;

                return $binding;
            },
            static fn (Binding $binding, Container $context): mixed => null,
            $this->createMock(Container::class),
        );

        $context->get($request);

        self::assertInstanceOf(Binding::class, $located);
        self::assertTrue($located->equals($request));
    }

    /** @return iterable<string, array{mixed}> */
    public static function provideResolvedValues(): iterable
    {
        yield 'object' => [new stdClass()];
        yield 'string' => ['value'];
        yield 'integer' => [42];
        yield 'null' => [null];
        yield 'array' => [[1, 2, 3]];
    }

    /**
     * @covers ::get
     * @covers ::__construct
     * @dataProvider provideResolvedValues
     */
    public function testGetReturnsWhatResolveReturns(mixed $value): void
    {
        $context = new ResolutionContext(
            static fn (Binding $binding): Binding => $binding,
            static fn (Binding $binding, Container $context): mixed => $value,
            $this->createMock(Container::class),
        );

        self::assertSame($value, $context->get(stdClass::class));
    }

    /**
     * @covers ::get
     * @covers ::__construct
     */
    public function testResolveReceivesLocatedBindingAndChildContext(): void
    {
        $located  = new Binding(ArrayObject::class, 'impl');
        $received = [];
        $context  = new ResolutionContext(
            static fn (Binding $binding): Binding => $located,
            static function (Binding $binding, Container $child) use (&$received): mixed {
                $received = [$binding, $child];

                return null;
            },
            $this->createMock(Container::class),
        );

        $context->get(stdClass::class);

        self::assertSame($located, $received[0]);
        self::assertInstanceOf(ResolutionContext::class, $received[1]);
        self::assertNotSame($context, $received[1]);
    }

    /** @return iterable<string, array{array<string, list<string>>, string, list<string>, list<string>}> */
    public static function provideCircularRequests(): iterable
    {
        $a = stdClass::class;
        $b = ArrayObject::class;
        $c = DateTime::class;

        yield 'self cycle' => [[$a => [$a]], $a, [], [$a, $a]];
        yield 'two node cycle' => [[$a => [$b], $b => [$a]], $a, [], [$a, $b, $a]];
        yield 'cycle not including root' => [
            [$a => [$b], $b => [$c], $c => [$b]],
            $a,
            [],
            [$a, $b, $c, $b],
        ];

        yield 'cycle through initial path' => [[$a => [$b], $b => [$c]], $a, [$c], [$c, $a, $b, $c]];
        yield 'root already on initial path' => [[], $a, [$a], [$a, $a]];
    }

    /**
     * @param array<string, list<string>> $graph
     * @param list<string>                $initial
     * @param list<string>                $expected
     *
     * @covers ::get
     * @covers ::__construct
     *
     * @dataProvider provideCircularRequests
     */
    public function testNestedRequestOfBindingOnPathThrowsCircularDependency(
        array $graph,
        string $root,
        array $initial,
        array $expected,
    ): void {
        $initialPath = [];

        foreach ($initial as $type) {
            $initialPath[] = new Binding($type);
        }

        $context = $this->graphContext($graph, $initialPath);

        try {
            $context->get($root);
            self::fail('CircularDependency expected');
        } catch (CircularDependency $e) {
            self::assertSame($expected, self::render($e->getPath()));
        }
    }

    /**
     * @covers ::get
     * @covers ::__construct
     */
    public function testCircularityIsDetectedOnLocatedBinding(): void
    {
        $impl    = new Binding(ArrayObject::class, 'impl');
        $context = new ResolutionContext(
            static fn (Binding $binding): Binding => $impl,
            static function (Binding $binding, Container $child): mixed {
                return $child->get(stdClass::class);
            },
            $this->createMock(Container::class),
        );

        try {
            $context->get(stdClass::class);
            self::fail('CircularDependency expected');
        } catch (CircularDependency $e) {
            self::assertSame([(string) $impl, (string) $impl], self::render($e->getPath()));
        }
    }

    /**
     * @covers ::get
     * @covers ::__construct
     */
    public function testSiblingRequestsOfSameBindingAreNotACycle(): void
    {
        $a       = stdClass::class;
        $b       = ArrayObject::class;
        $c       = DateTime::class;
        $d       = Throwable::class;
        $context = $this->graphContext([$a => [$b, $c], $b => [$d], $c => [$d]]);

        self::assertSame($a, $context->get($a));
    }

    /**
     * @covers ::get
     * @covers ::__construct
     */
    public function testSameBindingCanBeRequestedAgainAfterItsResolutionCompleted(): void
    {
        $a       = stdClass::class;
        $context = $this->graphContext([]);

        $context->get($a);

        self::assertSame($a, $context->get($a));
    }

    /** @return iterable<string, array{bool, list<string>}> */
    public static function provideFailureOrigins(): iterable
    {
        yield 'locate throws, empty path' => [true, []];
        yield 'resolve throws, empty path' => [false, []];
        yield 'locate throws, initial path' => [true, [DateTime::class, ArrayObject::class]];
        yield 'resolve throws, initial path' => [false, [DateTime::class, ArrayObject::class]];
    }

    /**
     * @param list<string> $initial
     *
     * @covers ::get
     * @covers ::__construct
     *
     * @dataProvider provideFailureOrigins
     */
    public function testOtherThrowablesAreWrappedInResolutionFailedWithRequestedBinding(
        bool $locateThrows,
        array $initial,
    ): void {
        $original    = new RuntimeException('boom');
        $thrower     = static function () use ($original): never {
            throw $original;
        };
        $initialPath = [];

        foreach ($initial as $type) {
            $initialPath[] = new Binding($type);
        }

        $context = new ResolutionContext(
            $locateThrows ? $thrower : static fn (Binding $binding): Binding => $binding,
            $locateThrows ? static fn (Binding $binding, Container $child): mixed => null : $thrower,
            $this->createMock(Container::class),
            $initialPath,
        );

        try {
            $context->get(stdClass::class);
            self::fail('ResolutionFailed expected');
        } catch (ResolutionFailed $e) {
            self::assertSame($original, $e->getPrevious());
            self::assertSame(
                [...$initial, stdClass::class],
                self::render($e->getPath()),
            );
        }
    }

    /**
     * @covers ::get
     * @covers ::__construct
     */
    public function testWrappedErrorPathUsesRequestedBindingNotLocatedOne(): void
    {
        $context = new ResolutionContext(
            static fn (Binding $binding): Binding => new Binding(ArrayObject::class),
            static function (Binding $binding, Container $child): never {
                throw new LogicException('boom');
            },
            $this->createMock(Container::class),
        );

        try {
            $context->get(stdClass::class);
            self::fail('ResolutionFailed expected');
        } catch (ResolutionFailed $e) {
            self::assertSame([stdClass::class], self::render($e->getPath()));
        }
    }

    /** @return iterable<string, array{Throwable}> */
    public static function provideThrowablesRethrownUnchanged(): iterable
    {
        yield 'resolution failed' => [new ResolutionFailed([new Binding(DateTime::class)], new Exception('inner'))];
        yield 'circular dependency' => [
            new CircularDependency([new Binding(DateTime::class), new Binding(DateTime::class)]),
        ];
    }

    /**
     * @covers ::get
     * @covers ::__construct
     * @dataProvider provideThrowablesRethrownUnchanged
     */
    public function testNestedResolutionExceptionsReachTheCallerAsTheSameInstance(Throwable $thrown): void
    {
        $context = new ResolutionContext(
            static fn (Binding $binding): Binding => $binding,
            static function (Binding $binding, Container $child) use ($thrown): mixed {
                if ($binding->getType() === stdClass::class) {
                    return $child->get(ArrayObject::class);
                }

                throw $thrown;
            },
            $this->createMock(Container::class),
        );

        try {
            $context->get(stdClass::class);
            self::fail('Exception expected');
        } catch (Throwable $e) {
            self::assertSame($thrown, $e);
        }
    }

    /** @return iterable<string, array{Closure(): Throwable}> */
    public static function provideDirectlyThrownResolutionExceptions(): iterable
    {
        yield 'resolution failed' => [
            static fn (): Throwable => new ResolutionFailed([new Binding(DateTime::class)], new Exception('inner')),
        ];

        yield 'circular dependency' => [
            static fn (): Throwable => new CircularDependency(
                [new Binding(DateTime::class), new Binding(DateTime::class)],
            ),
        ];
    }

    /**
     * @param Closure(): Throwable $factory
     *
     * @covers ::get
     * @covers ::__construct
     *
     * @dataProvider provideDirectlyThrownResolutionExceptions
     */
    public function testResolutionExceptionsThrownByLocateAreNotRewrapped(Closure $factory): void
    {
        $thrown  = $factory();
        $context = new ResolutionContext(
            static function (Binding $binding) use ($thrown): never {
                throw $thrown;
            },
            static fn (Binding $binding, Container $child): mixed => null,
            $this->createMock(Container::class),
        );

        try {
            $context->get(stdClass::class);
            self::fail('Exception expected');
        } catch (Throwable $e) {
            self::assertSame($thrown, $e);
        }
    }

    /** @return iterable<string, array{Binding|string, bool}> */
    public static function provideHasRequests(): iterable
    {
        yield 'string present' => [stdClass::class, true];
        yield 'string absent' => [stdClass::class, false];
        yield 'binding present' => [new Binding(ArrayObject::class, 'audit', 'App'), true];
        yield 'binding absent' => [new Binding(ArrayObject::class, 'audit', 'App'), false];
    }

    /**
     * @covers ::has
     * @covers ::__construct
     * @dataProvider provideHasRequests
     */
    public function testHasDelegatesToWrappedContainer(Binding|string $request, bool $answer): void
    {
        $container = $this->createMock(Container::class);
        $container->expects(self::once())
            ->method('has')
            ->with(self::identicalTo($request))
            ->willReturn($answer);

        $context = new ResolutionContext(
            static fn (Binding $binding): Binding => $binding,
            static fn (Binding $binding, Container $child): mixed => null,
            $container,
        );

        self::assertSame($answer, $context->has($request));
    }
}
