<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Decorator;

use Closure;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Decorator\Composition;
use Vivarium\Container\Injector;
use Vivarium\Container\Provider;
use Vivarium\Container\Provider\Fun;
use Vivarium\Container\Registry\EagerRegistry;
use Vivarium\Test\Container\Stub\StubCompositionDependency;

/** @coversDefaultClass \Vivarium\Container\Decorator\Composition */
final class CompositionTest extends TestCase
{
    /**
     * @param list<mixed> $args
     *
     * @covers ::enhance
     * @covers ::bindSlot
     * @covers ::__construct
     *
     * @dataProvider provideCompositions
     */
    public function testEnhance(Closure $g, string|int $slot, callable|string $f, array $args, mixed $expected): void
    {
        $composition = new Composition(new Fun($g), $slot);
        $h           = $composition->enhance($f, new Injector(new EagerRegistry()));

        self::assertIsCallable($h);
        self::assertSame($expected, $h(...$args));
    }

    /** @return iterable<string, array{Closure, string|int, callable|string, list<mixed>, mixed}> */
    public static function provideCompositions(): iterable
    {
        $sum = static fn (int $a, int $b): int => $a + $b;

        yield 'slot by name' => [static fn (int $n): int => $n * 10, 'n', $sum, [2, 3], 50];
        yield 'slot by position' => [static fn (int $n): int => $n * 10, 0, $sum, [2, 3], 50];
        yield 'function name' => [static fn (string $s): string => $s . '!', 's', 'strtoupper', ['a'], 'A!'];

        $dep = static fn (StubCompositionDependency $d, int $n): int => $n + $d->value();

        yield 'extra parameter autowired' => [$dep, 'n', $sum, [1, 2], 10];

        yield 'slot at second position' => [$dep, 1, $sum, [1, 2], 10];
    }

    /**
     * @covers ::enhance
     * @covers ::bindSlot
     * @covers ::__construct
     */
    public function testEnhancedCallableIsReusable(): void
    {
        $composition = new Composition(new Fun(static fn (int $n): int => $n * 10), 'n');
        $h           = $composition->enhance(
            static fn (int $a, int $b): int => $a + $b,
            new Injector(new EagerRegistry()),
        );

        self::assertSame(50, $h(2, 3));
        self::assertSame(70, $h(3, 4));
    }

    /**
     * @covers ::__construct
     * @covers ::bindSlot
     * @dataProvider provideInvalidSlots
     */
    public function testConstructionWithInvalidSlotFails(string|int $slot): void
    {
        $this->expectException(AssertionFailed::class);

        $composition = new Composition(new Fun(static fn (int $n): int => $n), $slot);
        $composition->enhance(static fn (int $a): int => $a, new Injector(new EagerRegistry()));
    }

    /** @return iterable<string, array{string|int}> */
    public static function provideInvalidSlots(): iterable
    {
        yield 'unknown name' => ['missing'];
        yield 'position out of range' => [5];
    }

    /**
     * @covers ::enhance
     * @covers ::bindSlot
     * @covers ::__construct
     */
    public function testEnhanceException(): void
    {
        $composition = new Composition(new Fun(static fn (int $n): int => $n), 'n');

        $this->expectException(AssertionFailed::class);
        $composition->enhance('not a callable', new Injector(new EagerRegistry()));
    }

    /**
     * @covers ::enhance
     * @covers ::bindSlot
     * @covers ::__construct
     */
    public function testCallFailsWhenResultIsNotAssignableToSlot(): void
    {
        $composition = new Composition(new Fun(static fn (int $n): int => $n), 'n');
        $h           = $composition->enhance(static fn (): string => 'text', new Injector(new EagerRegistry()));

        $this->expectException(AssertionFailed::class);
        $h();
    }

    /**
     * @covers ::accept
     * @covers ::__construct
     */
    public function testAccept(): void
    {
        $composition = new Composition(new Fun(static fn (int $n): int => $n), 'n');

        self::assertTrue($composition->accept($this->createMock(Provider::class)));
    }
}
