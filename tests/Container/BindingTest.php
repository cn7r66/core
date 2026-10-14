<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container;

use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Binding;
use Vivarium\Container\Exception\CannotBeWidened;
use Vivarium\Test\Container\Stub\Stub;
use Vivarium\Test\Container\Stub\StubBase;
use Vivarium\Test\Container\Stub\StubClass;

/** @coversDefaultClass \Vivarium\Container\Binding */
final class BindingTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getType
     * @covers ::getTag
     * @covers ::getContext
     */
    public function testDefaultsAreAppliedWhenOnlyTypeGiven(): void
    {
        $binding = new Binding(StubClass::class);

        static::assertSame(StubClass::class, $binding->getType());
        static::assertSame(Binding::DEFAULT, $binding->getTag());
        static::assertSame(Binding::GLOBAL, $binding->getContext());
    }

    /**
     * @covers ::__construct
     * @covers ::getType
     * @covers ::getTag
     * @covers ::getContext
     */
    public function testExplicitTagAndContextAreStored(): void
    {
        $binding = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');

        static::assertSame(StubClass::class, $binding->getType());
        static::assertSame('theTag', $binding->getTag());
        static::assertSame('Vivarium\Test\Container', $binding->getContext());
    }

    /**
     * @covers ::__construct
     * @dataProvider provideValidTypes
     */
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

    /** @covers ::__construct */
    public function testConstructRejectsInvalidType(): void
    {
        static::expectException(AssertionFailed::class);
        new Binding('theId');
    }

    /**
     * @covers ::couldBeWidened
     * @covers ::__construct
     * @dataProvider provideCouldBeWidenedCases
     */
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
            'class-custom-tag-global'        => [StubClass::class, 'theTag', Binding::GLOBAL, true],
            'class-default-class-context'    => [StubClass::class, Binding::DEFAULT, StubBase::class, true],
            'class-custom-tag-class-context' => [StubClass::class, 'theTag', StubBase::class, true],
        ];
    }

    /**
     * @covers ::widen
     * @covers ::getTag
     * @covers ::getContext
     * @covers ::__construct
     */
    public function testWidenRemovesTagFirst(): void
    {
        $binding = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');
        $widened = $binding->widen();

        static::assertSame(Binding::DEFAULT, $widened->getTag());
        static::assertSame('Vivarium\Test\Container', $widened->getContext());
    }

    /**
     * @covers ::widen
     * @covers ::getTag
     * @covers ::getContext
     * @covers ::__construct
     */
    public function testWidenNarrowsContextAfterTagIsDefault(): void
    {
        $binding = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');
        $widened = $binding->widen()->widen();

        static::assertSame(Binding::DEFAULT, $widened->getTag());
        static::assertSame('Vivarium\Test', $widened->getContext());
    }

    /**
     * @covers ::widen
     * @covers ::__construct
     */
    public function testWidenThrowsWhenAlreadyAtMinimum(): void
    {
        $binding = new Binding(StubClass::class);

        static::assertFalse($binding->couldBeWidened());
        static::expectException(CannotBeWidened::class);
        $binding->widen();
    }

    /**
     * @covers ::equals
     * @covers ::__construct
     */
    public function testSameInstanceIsEqual(): void
    {
        $binding = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');

        static::assertTrue($binding->equals($binding));
    }

    /**
     * @covers ::equals
     * @covers ::__construct
     */
    public function testBindingsWithIdenticalArgumentsAreEqual(): void
    {
        $a = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');
        $b = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');

        static::assertTrue($a->equals($b));
    }

    /**
     * @covers ::equals
     * @covers ::__construct
     */
    public function testEqualsReturnsFalseForNonBindingObject(): void
    {
        $binding = new Binding(StubClass::class);

        static::assertFalse($binding->equals(new stdClass()));
    }

    /**
     * @covers ::equals
     * @covers ::__construct
     */
    public function testBindingsWithDifferentContextAreNotEqual(): void
    {
        $a = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');
        $b = new Binding(StubClass::class, 'theTag', 'Vivarium\Test');

        static::assertFalse($a->equals($b));
    }

    /**
     * @covers ::equals
     * @covers ::__construct
     */
    public function testBindingsWithDifferentTypeAreNotEqual(): void
    {
        $a = new Binding(StubClass::class);
        $b = new Binding(stdClass::class);

        static::assertFalse($a->equals($b));
    }

    /**
     * @covers ::hash
     * @covers ::__construct
     */
    public function testSameArgumentsProduceSameHash(): void
    {
        $a = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');
        $b = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');

        static::assertSame($a->hash(), $b->hash());
    }

    /**
     * @covers ::hash
     * @covers ::__construct
     */
    public function testDifferentArgumentsProduceDifferentHash(): void
    {
        $a = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');
        $b = new Binding(StubClass::class, 'otherTag', 'Vivarium\Test\Container');

        static::assertNotSame($a->hash(), $b->hash());
    }

    /**
     * @covers ::hash
     * @covers ::__construct
     */
    public function testHashIsConsistentAcrossCalls(): void
    {
        $binding = new Binding(StubClass::class, 'theTag', 'Vivarium\Test\Container');

        static::assertSame($binding->hash(), $binding->hash());
    }

    /**
     * @covers ::hierarchy
     * @covers ::expand
     * @covers ::extends
     * @covers ::interfaces
     * @covers ::__construct
     */
    public function testHierarchyForInterfaceWithNoParentContainsOnlyItself(): void
    {
        $binding   = new Binding(Stub::class);
        $hierarchy = $binding->hierarchy();

        static::assertCount(1, $hierarchy);
    }

    /**
     * @covers ::hierarchy
     * @covers ::expand
     * @covers ::extends
     * @covers ::interfaces
     * @covers ::__construct
     */
    public function testHierarchyForClassWithParentsContainsAllAncestors(): void
    {
        $binding   = new Binding(StubClass::class);
        $hierarchy = $binding->hierarchy();

        static::assertCount(3, $hierarchy);
    }

    /**
     * @covers ::hierarchy
     * @covers ::expand
     * @covers ::extends
     * @covers ::interfaces
     * @covers ::__construct
     */
    public function testHierarchyOrderIsMostSpecificFirst(): void
    {
        $binding   = new Binding(StubClass::class);
        $hierarchy = $binding->hierarchy();

        static::assertSame(StubClass::class, $hierarchy->getAtIndex(0)->getType());
        static::assertSame(Stub::class, $hierarchy->getAtIndex($hierarchy->count() - 1)->getType());
    }

    /**
     * @covers ::hierarchy
     * @covers ::expand
     * @covers ::extends
     * @covers ::interfaces
     * @covers ::__construct
     */
    public function testHierarchyWithTagAndContextExpandsAllLevels(): void
    {
        $binding   = new Binding(StubClass::class, 'myTag', 'Vivarium\Test\Container');
        $hierarchy = $binding->hierarchy();

        static::assertCount(13, $hierarchy);
    }

    /**
     * @covers ::__toString
     * @covers ::__construct
     * @dataProvider provideToStringCases
     */
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
}
