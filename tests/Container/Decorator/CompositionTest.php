<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Decorator;

use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Decorator\Composition;
use Vivarium\Container\Injector;
use Vivarium\Container\Provider;
use Vivarium\Container\Provider\Fun;
use Vivarium\Container\Registry\EagerRegistry;
use Vivarium\Test\Container\Stub\StubCompositionDependency;

#[CoversClass(Composition::class)]
final class CompositionTest extends TestCase
{
    /** @param list<mixed> $args */
    #[DataProvider('provideCompositions')]
    public function testEnhance(
        Closure $outer,
        string|int $slot,
        callable|string $inner,
        array $args,
        mixed $expected,
    ): void {
        $composition = new Composition(new Fun($outer), $slot);
        $composed    = $composition->enhance($inner, new Injector(new EagerRegistry()));

        self::assertIsCallable($composed);
        self::assertSame($expected, $composed(...$args));
    }

    /** @return array<string, array{Closure, string|int, callable|string, list<mixed>, mixed}> */
    public static function provideCompositions(): array
    {
        $sum            = static fn (int $first, int $second): int => $first + $second;
        $timesTen       = static fn (int $number): int => $number * 10;
        $plusDependency = static fn (StubCompositionDependency $dependency, int $number): int => $number
            + $dependency->value();

        return [
            'slot by name' => [$timesTen, 'number', $sum, [2, 3], 50],
            'slot by position' => [$timesTen, 0, $sum, [2, 3], 50],
            'function name' => [static fn (string $text): string => $text . '!', 'text', 'strtoupper', ['a'], 'A!'],
            'extra parameter autowired' => [$plusDependency, 'number', $sum, [1, 2], 10],
            'slot at second position' => [$plusDependency, 1, $sum, [1, 2], 10],
        ];
    }

    public function testEnhancedCallableIsReusable(): void
    {
        $composition = new Composition(new Fun(static fn (int $number): int => $number * 10), 'number');
        $composed    = $composition->enhance(
            static fn (int $first, int $second): int => $first + $second,
            new Injector(new EagerRegistry()),
        );

        self::assertSame(50, $composed(2, 3));
        self::assertSame(70, $composed(3, 4));
    }

    #[DataProvider('provideInvalidSlots')]
    public function testConstructionWithInvalidSlotFails(string|int $slot): void
    {
        $this->expectException(AssertionFailed::class);

        new Composition(new Fun(static fn (int $number): int => $number), $slot);
    }

    /** @return array<string, array{string|int}> */
    public static function provideInvalidSlots(): array
    {
        return [
            'unknown name' => ['missing'],
            'position out of range' => [5],
        ];
    }

    public function testEnhanceRejectsANonCallableValue(): void
    {
        $composition = new Composition(new Fun(static fn (int $number): int => $number), 'number');

        $this->expectException(AssertionFailed::class);
        $composition->enhance('not a callable', new Injector(new EagerRegistry()));
    }

    public function testCallFailsWhenResultIsNotAssignableToSlot(): void
    {
        $composition = new Composition(new Fun(static fn (int $number): int => $number), 'number');
        $composed    = $composition->enhance(static fn (): string => 'text', new Injector(new EagerRegistry()));

        $this->expectException(AssertionFailed::class);
        $composed();
    }

    public function testAccept(): void
    {
        $composition = new Composition(new Fun(static fn (int $number): int => $number), 'number');

        self::assertTrue($composition->accept($this->createStub(Provider::class)));
    }
}
