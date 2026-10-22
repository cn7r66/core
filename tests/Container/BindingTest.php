<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Binding;
use Vivarium\Container\Exception\CannotBeWidened;
use Vivarium\Test\Container\Stub\Stub;
use Vivarium\Test\Container\Stub\StubBase;
use Vivarium\Test\Container\Stub\StubClass;

#[CoversClass(Binding::class)]
final class BindingTest extends TestCase
{
    public function testDefaultsAreAppliedWhenOnlyTypeGiven(): void
    {
        $binding = new Binding(StubClass::class);

        static::assertSame(StubClass::class, $binding->getType());
        static::assertSame(Binding::DEFAULT, $binding->getTag());
        static::assertSame(Binding::GLOBAL, $binding->getContext());
    }

    public function testExplicitTagAndContextAreStored(): void
    {
        $binding = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');

        static::assertSame(StubClass::class, $binding->getType());
        static::assertSame('theTag', $binding->getTag());
        static::assertSame('Vivarium\Test\Container', $binding->getContext());
    }

    #[DataProvider('provideValidTypes')]
    public function testConstructAcceptsValidTypes(string $type): void
    {
        $binding = new Binding($type);

        static::assertSame($type, $binding->getType());
    }

    /** @return array<string, array{string}> */
    public static function provideValidTypes(): array
    {
        return [
            'int'          => ['int'],
            'float'        => ['float'],
            'string'       => ['string'],
            'array'        => ['array'],
            'callable'     => ['callable'],
            'object'       => ['object'],
            'bool'         => ['bool'],
            'class'        => [StubClass::class],
            'interface'    => [Stub::class],
            'intersection' => ['Vivarium\Test\Container\Stub\Stub&Stringable'],
            'union'        => ['string|' . Stringable::class],
        ];
    }

    public function testConstructRejectsInvalidType(): void
    {
        static::expectException(AssertionFailed::class);
        new Binding('theId');
    }

    #[DataProvider('provideCouldBeWidenedCases')]
    public function testCouldBeWidenedReturnsExpectedResult(
        string $type,
        string $tag,
        string $context,
        bool $expected,
    ): void {
        $binding = new Binding($type, $tag, $context);

        static::assertSame($expected, $binding->couldBeWidened());
    }

    /** @return array<string, array{string, string, string, bool}> */
    public static function provideCouldBeWidenedCases(): array
    {
        return [
            'string-id-default-global'       => ['string', Binding::DEFAULT, Binding::GLOBAL, false],
            'class-default-global'           => [StubClass::class, Binding::DEFAULT, Binding::GLOBAL, false],
            'class-custom-tag-global'        => [StubClass::class, 'theTag', Binding::GLOBAL, false],
            'class-default-class-context'    => [StubClass::class, Binding::DEFAULT, StubBase::class, true],
            'class-custom-tag-class-context' => [StubClass::class, 'theTag', StubBase::class, true],
        ];
    }

    #[DataProvider('provideWidenCases')]
    public function testWidenKeepsTagAndMovesContextUpOneLevel(
        string $tag,
        string $context,
        string $expectedContext,
    ): void {
        $widened = (new Binding(StubClass::class, $tag, $context))->widen();

        static::assertSame(StubClass::class, $widened->getType());
        static::assertSame($tag, $widened->getTag());
        static::assertSame($expectedContext, $widened->getContext());
    }

    /** @return array<string, array{string, string, string}> */
    public static function provideWidenCases(): array
    {
        return [
            'custom-tag-three-levels' => ['theTag', 'App\Orders\Checkout', 'App\Orders'],
            'custom-tag-two-levels'   => ['theTag', 'App\Orders', 'App'],
            'custom-tag-one-level'    => ['theTag', 'App', Binding::GLOBAL],
            'default-tag-two-levels'  => [Binding::DEFAULT, 'App\Orders', 'App'],
        ];
    }

    public function testWidenTwiceKeepsTagAndMovesContextUpTwoLevels(): void
    {
        $binding = new Binding(StubClass::class, 'theTag', 'App\Orders\Checkout');
        $widened = $binding->widen()->widen();

        static::assertSame('theTag', $widened->getTag());
        static::assertSame('App', $widened->getContext());
    }

    public function testWidenThrowsWhenAlreadyAtMinimum(): void
    {
        $binding = new Binding(StubClass::class);

        static::assertFalse($binding->couldBeWidened());
        static::expectException(CannotBeWidened::class);
        $binding->widen();
    }

    #[DataProvider('provideEqualityComparisons')]
    public function testEqualsComparesTypeTagAndContext(Binding $binding, object $other, bool $expected): void
    {
        static::assertSame($expected, $binding->equals($other));
    }

    /** @return array<string, array{Binding, object, bool}> */
    public static function provideEqualityComparisons(): array
    {
        $binding = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');

        return [
            'same instance' => [$binding, $binding, true],
            'identical arguments' => [
                new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container'),
                new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container'),
                true,
            ],
            'non-binding object' => [new Binding(StubClass::class), new stdClass(), false],
            'different context' => [
                new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container'),
                new Binding(StubClass::class, 'theTag', 'Vivarium\Test'),
                false,
            ],
            'different type' => [new Binding(StubClass::class), new Binding(stdClass::class), false],
        ];
    }

    public function testSameArgumentsProduceSameHash(): void
    {
        $first  = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');
        $second = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');

        static::assertSame($first->hash(), $second->hash());
    }

    public function testDifferentArgumentsProduceDifferentHash(): void
    {
        $first  = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');
        $second = new Binding(StubClass::class, 'otherTag', 'Vivarium\Test\Container');

        static::assertNotSame($first->hash(), $second->hash());
    }

    public function testHashIsConsistentAcrossCalls(): void
    {
        $binding = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');

        static::assertSame($binding->hash(), $binding->hash());
    }

    #[DataProvider('provideTypesWithHierarchySize')]
    public function testHierarchyContainsTheTypeAndAllItsAncestors(string $type, int $expectedSize): void
    {
        $hierarchy = (new Binding($type))->hierarchy();

        static::assertCount($expectedSize, $hierarchy);
    }

    /** @return array<string, array{string, int}> */
    public static function provideTypesWithHierarchySize(): array
    {
        return [
            'interface with no parent' => [Stub::class, 1],
            'class with parents' => [StubClass::class, 3],
        ];
    }

    public function testHierarchyOrderIsMostSpecificFirst(): void
    {
        $binding   = new Binding(StubClass::class);
        $hierarchy = $binding->hierarchy();

        static::assertSame(StubClass::class, $hierarchy->getAtIndex(0)->getType());
        static::assertSame(Stub::class, $hierarchy->getAtIndex($hierarchy->count() - 1)->getType());
    }

    public function testHierarchyWithTagAndContextExpandsAllLevels(): void
    {
        $binding   = new Binding(StubClass::class, 'myTag', 'Vivarium\Test\Container');
        $hierarchy = $binding->hierarchy();

        static::assertCount(12, $hierarchy);

        for ($i = 0; $i < $hierarchy->count(); $i++) {
            static::assertSame('myTag', $hierarchy->getAtIndex($i)->getTag());
        }
    }

    #[DataProvider('provideToStringCases')]
    public function testToStringRendersTagAndContextOnlyWhenNonDefault(
        string $type,
        string $tag,
        string $context,
        string $expected,
    ): void {
        $binding = new Binding($type, $tag, $context);

        static::assertSame($expected, (string) $binding);
    }

    /** @return array<string, array{string, string, string, string}> */
    public static function provideToStringCases(): array
    {
        $type = StubClass::class;

        return [
            'type-only'          => [$type, Binding::DEFAULT, Binding::GLOBAL, $type],
            'custom-tag'         => [$type, 'audit', Binding::GLOBAL, $type . ':audit'],
            'custom-context'     => [$type, Binding::DEFAULT, 'App\Orders', $type . ' (in App\Orders)'],
            'custom-tag-context' => [$type, 'audit', 'App\Orders', $type . ':audit (in App\Orders)'],
        ];
    }

    #[DataProvider('provideIsMoreSpecificThanCases')]
    public function testIsMoreSpecificThanOrdersBySubtypeThenContext(
        Binding $subject,
        Binding $other,
        bool $expected,
    ): void {
        self::assertSame($expected, $subject->isMoreSpecificThan($other));
    }

    /** @return array<string, array{Binding, Binding, bool}> */
    public static function provideIsMoreSpecificThanCases(): array
    {
        $sub   = StubClass::class;
        $super = StubBase::class;

        return [
            'different tags'                 => [new Binding($sub, 'audit'), new Binding($super), false],
            'subtype vs supertype'           => [new Binding($sub), new Binding($super), true],
            'supertype vs subtype'           => [new Binding($super), new Binding($sub), false],
            'unrelated types'                => [new Binding($sub), new Binding(stdClass::class), false],
            'type beats context'             => [
                new Binding($sub),
                new Binding($super, Binding::DEFAULT, 'App\\Foo'),
                true,
            ],
            'identical binding'              => [new Binding($sub), new Binding($sub), false],
            'namespace vs global'            => [
                new Binding($sub, Binding::DEFAULT, 'App\\Foo'),
                new Binding($sub),
                true,
            ],
            'global vs namespace'            => [
                new Binding($sub),
                new Binding($sub, Binding::DEFAULT, 'App\\Foo'),
                false,
            ],
            'nested namespace vs parent'     => [
                new Binding($sub, Binding::DEFAULT, 'App\\Foo\\Bar'),
                new Binding($sub, Binding::DEFAULT, 'App\\Foo'),
                true,
            ],
            'sibling sharing string prefix'  => [
                new Binding($sub, Binding::DEFAULT, 'App\\FooBar'),
                new Binding($sub, Binding::DEFAULT, 'App\\Foo'),
                false,
            ],
            'parent namespace vs nested'     => [
                new Binding($sub, Binding::DEFAULT, 'App\\Foo'),
                new Binding($sub, Binding::DEFAULT, 'App\\Foo\\Bar'),
                false,
            ],
        ];
    }
}
