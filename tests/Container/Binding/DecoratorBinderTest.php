<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Binding;

use Closure;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Comparator\Priority;
use Vivarium\Container\Binding;
use Vivarium\Container\Binding\DecoratorBinder;
use Vivarium\Container\Binding\PriorityBinder;
use Vivarium\Container\Decorator;
use Vivarium\Container\Injector;
use Vivarium\Container\Provider\Constructor;
use Vivarium\Container\Registry\EagerRegistry;
use Vivarium\Test\Container\Stub\StubClass;
use Vivarium\Test\Container\Stub\StubDecorating;
use Vivarium\Test\Container\Stub\StubDecoratingBase;
use Vivarium\Test\Container\Stub\StubDecoratingConfigurable;
use Vivarium\Test\Container\Stub\StubDecoratingMismatched;
use Vivarium\Test\Container\Stub\StubDecoratingVariadic;
use Vivarium\Test\Container\Stub\StubDecoratingWrapper;
use Vivarium\Test\Container\Stub\StubService;

/** @coversDefaultClass \Vivarium\Container\Binding\DecoratorBinder */
final class DecoratorBinderTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::withDecorator
     */
    public function testWithDecoratorReturnsPriorityBinder(): void
    {
        $binder = new DecoratorBinder(
            new Binding(StubClass::class),
            static fn (Decorator $dec, int $priority): string => 'result',
            Priority::NORMAL,
        );

        static::assertInstanceOf(PriorityBinder::class, $binder->withDecorator($this->createMock(Decorator::class)));
    }

    /**
     * @covers ::__construct
     * @covers ::withDecorator
     * @covers \Vivarium\Container\Binding\PriorityBinder::withDefaultPriority
     */
    public function testWithDecoratorForwardsDecoratorToCreate(): void
    {
        $decorator = $this->createMock(Decorator::class);
        $captured  = null;

        $binder = new DecoratorBinder(
            new Binding(StubClass::class),
            static function (Decorator $dec, int $priority) use (&$captured): string {
                $captured = $dec;

                return 'result';
            },
            Priority::NORMAL,
        );

        $binder->withDecorator($decorator)->withDefaultPriority();

        static::assertSame($decorator, $captured);
    }

    /**
     * @covers ::__construct
     * @covers ::withDecorator
     * @covers \Vivarium\Container\Binding\PriorityBinder::withDefaultPriority
     * @dataProvider provideDefaultPriorities
     */
    public function testWithDefaultPriorityPassesTheDefaultGivenAtConstruction(int $defaultPriority): void
    {
        $capturedPriority = null;

        $binder = new DecoratorBinder(
            new Binding(StubClass::class),
            static function (Decorator $dec, int $priority) use (&$capturedPriority): int {
                $capturedPriority = $priority;

                return $priority;
            },
            $defaultPriority,
        );

        $binder->withDecorator($this->createMock(Decorator::class))->withDefaultPriority();

        static::assertSame($defaultPriority, $capturedPriority);
    }

    /** @return array<string, array{int}> */
    public static function provideDefaultPriorities(): array
    {
        return [
            'low' => [Priority::LOW],
            'high' => [Priority::HIGH],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::withDecorator
     * @covers \Vivarium\Container\Binding\PriorityBinder::withPriority
     */
    public function testWithPriorityPassesExplicitPriorityToCreate(): void
    {
        $capturedPriority = null;

        $binder = new DecoratorBinder(
            new Binding(StubClass::class),
            static function (Decorator $dec, int $priority) use (&$capturedPriority): int {
                $capturedPriority = $priority;

                return $priority;
            },
            Priority::NORMAL,
        );

        $binder->withDecorator($this->createMock(Decorator::class))->withPriority(42);

        static::assertSame(42, $capturedPriority);
    }

    /**
     * @covers ::__construct
     * @covers ::withDecorator
     * @covers \Vivarium\Container\Binding\PriorityBinder::withDefaultPriority
     */
    public function testWithDefaultPriorityReturnsValueFromCreate(): void
    {
        $expected = new stdClass();

        $binder = new DecoratorBinder(
            new Binding(StubClass::class),
            static fn (Decorator $dec, int $priority): object => $expected,
            Priority::NORMAL,
        );

        $result = $binder->withDecorator($this->createMock(Decorator::class))->withDefaultPriority();

        static::assertSame($expected, $result);
    }

    /**
     * @covers ::__construct
     * @covers ::withDecorator
     * @covers \Vivarium\Container\Binding\PriorityBinder::withPriority
     */
    public function testWithPriorityReturnsValueFromCreate(): void
    {
        $expected = new stdClass();

        $binder = new DecoratorBinder(
            new Binding(StubClass::class),
            static fn (Decorator $dec, int $priority): object => $expected,
            Priority::NORMAL,
        );

        $result = $binder->withDecorator($this->createMock(Decorator::class))->withPriority(42);

        static::assertSame($expected, $result);
    }

    /**
     * @covers ::__construct
     * @covers ::with
     * @covers ::assertSlot
     * @covers ::slotParameter
     * @covers \Vivarium\Container\Binding\PriorityBinder::withDefaultPriority
     */
    public function testWithCreatesWrapperDecorator(): void
    {
        $captured = null;
        $binder   = $this->capturing($captured, new Binding(StubDecorating::class));

        $binder->with(StubDecoratingWrapper::class, 'inner')->withDefaultPriority();

        static::assertInstanceOf(Decorator\Wrapper::class, $captured);
    }

    /**
     * @covers ::__construct
     * @covers ::with
     * @covers ::assertSlot
     * @covers ::slotParameter
     * @covers \Vivarium\Container\Binding\PriorityBinder::withPriority
     */
    public function testWithReturnsValueFromCreateAndForwardsPriority(): void
    {
        $received = null;
        $binder   = new DecoratorBinder(
            new Binding(StubDecorating::class),
            static function (Decorator $dec, int $priority) use (&$received): string {
                $received = $priority;

                return 'created';
            },
            Priority::NORMAL,
        );

        $result = $binder->with(StubDecoratingWrapper::class, 'inner')->withPriority(7);

        static::assertSame(['created', 7], [$result, $received]);
    }

    /**
     * @covers ::__construct
     * @covers ::with
     * @covers ::assertSlot
     * @covers ::slotParameter
     * @covers \Vivarium\Container\Binding\PriorityBinder::withDefaultPriority
     * @dataProvider provideValidSlots
     */
    public function testWithWrapperPutsDecoratedObjectInSlot(string|int $slot): void
    {
        $captured = null;
        $binder   = $this->capturing($captured, new Binding(StubDecorating::class));
        $base     = new StubDecoratingBase();

        $binder->with(StubDecoratingWrapper::class, $slot)->withDefaultPriority();
        $result = $captured->enhance($base, new Injector(new EagerRegistry()));

        static::assertInstanceOf(StubDecoratingWrapper::class, $result);
        static::assertSame($base, $result->inner);
    }

    /** @return array<string, array{string|int}> */
    public static function provideValidSlots(): array
    {
        return [
            'by name' => ['inner'],
            'by position' => [0],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::with
     * @covers ::assertSlot
     * @covers ::slotParameter
     * @covers \Vivarium\Container\Binding\PriorityBinder::withDefaultPriority
     */
    public function testWithAppliesConfigureCallback(): void
    {
        $captured = null;
        $binder   = $this->capturing($captured, new Binding(StubDecorating::class));
        $base     = new StubDecoratingBase();

        $binder->with(
            StubDecoratingConfigurable::class,
            'inner',
            static fn (Constructor $c): Constructor => $c->bindArgument('suffix')->toInstance('!'),
        )->withDefaultPriority();
        $result = $captured->enhance($base, new Injector(new EagerRegistry()));

        static::assertSame('base!', $result->label());
    }

    /**
     * @covers ::__construct
     * @covers ::with
     * @covers ::assertSlot
     * @covers ::slotParameter
     * @dataProvider provideInvalidWithArguments
     */
    public function testWithRejectsInvalidRegistration(string $class, string|int $slot): void
    {
        $binder = new DecoratorBinder(
            new Binding(StubDecorating::class),
            static fn (Decorator $dec, int $priority): Decorator => $dec,
            Priority::NORMAL,
        );

        $this->expectException(AssertionFailed::class);

        $binder->with($class, $slot);
    }

    /** @return array<string, array{string, string|int}> */
    public static function provideInvalidWithArguments(): array
    {
        return [
            'class not assignable to binding type' => [StubService::class, 'inner'],
            'unknown slot name' => [StubDecoratingWrapper::class, 'missing'],
            'position out of range' => [StubDecoratingWrapper::class, 5],
            'variadic slot by name' => [StubDecoratingVariadic::class, 'inners'],
            'variadic slot by position' => [StubDecoratingVariadic::class, 0],
            'binding type not assignable to slot type' => [StubDecoratingMismatched::class, 'inner'],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::withFunction
     * @covers ::assertSlot
     * @covers ::slotParameter
     * @covers \Vivarium\Container\Binding\PriorityBinder::withDefaultPriority
     * @dataProvider provideValidFunctions
     */
    public function testWithFunctionCreatesWrapperDecorator(Closure $fn, string|int $slot): void
    {
        $captured = null;
        $binder   = $this->capturing($captured, new Binding(StubDecorating::class));

        $binder->withFunction($fn, $slot)->withDefaultPriority();

        static::assertInstanceOf(Decorator\Wrapper::class, $captured);
    }

    /** @return array<string, array{Closure, string|int}> */
    public static function provideValidFunctions(): array
    {
        return [
            'with return type' => [static fn (StubDecorating $inner): StubDecorating => $inner, 'inner'],
            'without return type' => [static fn (StubDecorating $inner) => $inner, 0],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::withFunction
     * @covers ::assertSlot
     * @covers ::slotParameter
     * @covers \Vivarium\Container\Binding\PriorityBinder::withDefaultPriority
     */
    public function testWithFunctionPutsDecoratedObjectInSlot(): void
    {
        $captured = null;
        $binder   = $this->capturing($captured, new Binding(StubDecorating::class));
        $base     = new StubDecoratingBase();

        $binder->withFunction(
            static fn (StubDecorating $inner): StubDecorating => new StubDecoratingWrapper($inner),
            'inner',
        )->withDefaultPriority();
        $result = $captured->enhance($base, new Injector(new EagerRegistry()));

        static::assertInstanceOf(StubDecoratingWrapper::class, $result);
        static::assertSame($base, $result->inner);
    }

    /**
     * @covers ::__construct
     * @covers ::withFunction
     * @covers ::assertSlot
     * @covers ::slotParameter
     * @dataProvider provideInvalidFunctions
     */
    public function testWithFunctionRejectsInvalidRegistration(Closure $fn, string|int $slot): void
    {
        $binder = new DecoratorBinder(
            new Binding(StubDecorating::class),
            static fn (Decorator $dec, int $priority): Decorator => $dec,
            Priority::NORMAL,
        );

        $this->expectException(AssertionFailed::class);

        $binder->withFunction($fn, $slot);
    }

    /** @return array<string, array{Closure, string|int}> */
    public static function provideInvalidFunctions(): array
    {
        $wrongReturnType = static fn (StubDecorating $inner): StubService => new StubService();

        return [
            'unknown slot name' => [static fn (StubDecorating $inner): StubDecorating => $inner, 'missing'],
            'position out of range' => [static fn (StubDecorating $inner): StubDecorating => $inner, 3],
            'variadic slot' => [static fn (StubDecorating ...$inners): StubDecorating => $inners[0], 'inners'],
            'slot type not accepting binding type' => [static fn (StubService $service) => $service, 'service'],
            'return type not assignable' => [$wrongReturnType, 'inner'],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::compose
     * @covers ::slotParameter
     * @covers \Vivarium\Container\Binding\PriorityBinder::withDefaultPriority
     * @dataProvider provideCallableTypes
     */
    public function testComposeCreatesCompositionDecorator(string $type): void
    {
        $captured = null;
        $binder   = $this->capturing($captured, new Binding($type));

        $binder->compose(static fn (string $value): string => $value, 'value')->withDefaultPriority();

        static::assertInstanceOf(Decorator\Composition::class, $captured);
    }

    /** @return array<string, array{string}> */
    public static function provideCallableTypes(): array
    {
        return [
            'callable' => ['callable'],
            'Closure' => [Closure::class],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::compose
     * @covers ::slotParameter
     * @covers \Vivarium\Container\Binding\PriorityBinder::withDefaultPriority
     * @dataProvider provideValidSlotsForCompose
     */
    public function testComposeProducesCallableEquivalentToGOfF(string|int $slot): void
    {
        $captured = null;
        $binder   = $this->capturing($captured, new Binding(Closure::class));

        $binder->compose(static fn (string $result): string => $result . 'g', $slot)->withDefaultPriority();
        $composed = $captured->enhance(
            static fn (string $value): string => $value . 'f',
            new Injector(new EagerRegistry()),
        );

        static::assertSame('xfg', $composed('x'));
    }

    /** @return array<string, array{string|int}> */
    public static function provideValidSlotsForCompose(): array
    {
        return [
            'by name' => ['result'],
            'by position' => [0],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::compose
     * @covers ::slotParameter
     * @dataProvider provideInvalidComposeArguments
     */
    public function testComposeRejectsInvalidRegistration(string $type, Closure $fn, string|int $slot): void
    {
        $binder = new DecoratorBinder(
            new Binding($type),
            static fn (Decorator $dec, int $priority): Decorator => $dec,
            Priority::NORMAL,
        );

        $this->expectException(AssertionFailed::class);

        $binder->compose($fn, $slot);
    }

    /** @return array<string, array{string, Closure, string|int}> */
    public static function provideInvalidComposeArguments(): array
    {
        return [
            'binding type not callable' => [StubService::class, static fn (string $value): string => $value, 'value'],
            'unknown slot name' => [Closure::class, static fn (string $value): string => $value, 'missing'],
            'position out of range' => [Closure::class, static fn (string $value): string => $value, 4],
            'variadic slot' => [Closure::class, static fn (string ...$values): string => 'x', 'values'],
        ];
    }

    private function capturing(Decorator|null &$captured, Binding $binding): DecoratorBinder
    {
        return new DecoratorBinder(
            $binding,
            static function (Decorator $dec, int $priority) use (&$captured): Decorator {
                $captured = $dec;

                return $dec;
            },
            Priority::NORMAL,
        );
    }
}
