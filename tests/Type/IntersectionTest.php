<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Type;

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use Countable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SplObjectStorage;
use stdClass;
use Traversable;
use Vivarium\Test\Type\Stub\StubType;
use Vivarium\Type\Clazz;
use Vivarium\Type\Exception\NotAnIntersection;
use Vivarium\Type\Intersection;
use Vivarium\Type\Primitive;
use Vivarium\Type\Type;
use Vivarium\Type\Union;

use function array_map;
use function strval;

#[CoversClass(Intersection::class)]
final class IntersectionTest extends TestCase
{
    /** @param list<string> $members */
    #[DataProvider('provideInvalidMembers')]
    public function testConstructorException(array $members, string $message): void
    {
        $this->expectException(NotAnIntersection::class);
        $this->expectExceptionMessage($message);

        new Intersection(...array_map(static fn (string $name): Clazz => new Clazz($name), $members));
    }

    #[DataProvider('provideNonClassMembers')]
    public function testConstructorRejectsNonClassMember(Type $member, string $message): void
    {
        $this->expectException(NotAnIntersection::class);
        $this->expectExceptionMessage($message);

        new Intersection(new Clazz(Countable::class), $member);
    }

    #[DataProvider('provideIsRedundantWith')]
    public function testIsRedundantWith(Intersection $intersection, Clazz|Intersection $other, bool $expected): void
    {
        self::assertSame($expected, $intersection->isRedundantWith($other));
    }

    /**
     * @param list<string> $members
     * @param list<string> $expected
     */
    #[DataProvider('provideGetMembers')]
    public function testGetMembers(array $members, array $expected): void
    {
        $intersection = new Intersection(...array_map(static fn (string $name): Clazz => new Clazz($name), $members));

        self::assertSame($expected, array_map(strval(...), $intersection->getMembers()));
    }

    #[DataProvider('provideAccepts')]
    public function testAccepts(Intersection $target, Type $source, bool $expected): void
    {
        self::assertSame($expected, $target->accepts($source));
    }

    #[DataProvider('provideAcceptsVar')]
    public function testAcceptsVar(Intersection $target, mixed $value, bool $expected): void
    {
        self::assertSame($expected, $target->acceptsVar($value));
    }

    /** @param list<string> $members */
    #[DataProvider('provideToString')]
    public function testToString(array $members, string $expected): void
    {
        $intersection = new Intersection(...array_map(static fn (string $name): Clazz => new Clazz($name), $members));

        self::assertSame($expected, (string) $intersection);
    }

    #[DataProvider('provideEquals')]
    public function testEquals(Intersection $intersection, object $other, bool $expected): void
    {
        self::assertSame($expected, $intersection->equals($other));
    }

    #[DataProvider('provideHash')]
    public function testHash(Intersection $a, Intersection $b, bool $expectedEqual): void
    {
        self::assertSame($expectedEqual, $a->hash() === $b->hash());
    }

    /** @param list<Type> $members */
    #[DataProvider('provideFormat')]
    public function testFormat(array $members, string $expected): void
    {
        self::assertSame($expected, Intersection::format(...$members));
    }

    /** @return array<string, array{list<string>, string}> */
    public static function provideInvalidMembers(): array
    {
        return [
            'no members' => [
                [],
                'Expected a valid intersection: an intersection needs at least two members. Got string "".',
            ],
            'single member' => [
                [Countable::class],
                'Expected a valid intersection: an intersection needs at least two members.'
                    . ' Got string "Countable".',
            ],
            'duplicate member' => [
                [Countable::class, Countable::class],
                'Expected a valid intersection: duplicate type "Countable". Got string "Countable&Countable".',
            ],
            'duplicate among others' => [
                [Countable::class, Traversable::class, Countable::class],
                'Expected a valid intersection: duplicate type "Countable".'
                    . ' Got string "Countable&Traversable&Countable".',
            ],
        ];
    }

    /** @return array<string, array{Type, string}> */
    public static function provideNonClassMembers(): array
    {
        return [
            'primitive' => [
                new Primitive('int'),
                'Expected a valid intersection: "int" cannot be part of an intersection. Got string "Countable&int".',
            ],
            'union' => [
                new Union(new Clazz(Traversable::class), new Primitive('array')),
                'Expected a valid intersection: "Traversable|array" cannot be part of an intersection.'
                    . ' Got string "Countable&(Traversable|array)".',
            ],
            'foreign type' => [
                new StubType(),
                'Expected a valid intersection: "Stub" cannot be part of an intersection. Got string "Countable&Stub".',
            ],
        ];
    }

    /** @return array<string, array{Intersection, Clazz|Intersection, bool}> */
    public static function provideIsRedundantWith(): array
    {
        $countable   = new Clazz(Countable::class);
        $traversable = new Clazz(Traversable::class);
        $arrayAccess = new Clazz(ArrayAccess::class);

        return [
            'contains the class' => [new Intersection($countable, $traversable), $countable, true],
            'contains the intersection' => [
                new Intersection($countable, $traversable, $arrayAccess),
                new Intersection($countable, $traversable),
                true,
            ],
            'same intersection in another order' => [
                new Intersection($countable, $traversable),
                new Intersection($traversable, $countable),
                true,
            ],
            'misses the class' => [new Intersection($countable, $traversable), $arrayAccess, false],
            'overlaps the intersection' => [
                new Intersection($countable, $traversable),
                new Intersection($countable, $arrayAccess),
                false,
            ],
            'narrower than the intersection' => [
                new Intersection($countable, $traversable),
                new Intersection($countable, $traversable, $arrayAccess),
                false,
            ],
        ];
    }

    /** @return array<string, array{list<string>, list<string>}> */
    public static function provideGetMembers(): array
    {
        return [
            'already sorted' => [
                [Countable::class, Traversable::class],
                ['Countable', 'Traversable'],
            ],
            'reversed' => [
                [Traversable::class, Countable::class],
                ['Countable', 'Traversable'],
            ],
            'three members unsorted' => [
                [Traversable::class, Countable::class, ArrayAccess::class],
                ['ArrayAccess', 'Countable', 'Traversable'],
            ],
            'class with interface' => [
                [stdClass::class, ArrayObject::class],
                ['ArrayObject', 'stdClass'],
            ],
        ];
    }

    /** @return array<string, array{Intersection, Type, bool}> */
    public static function provideAccepts(): array
    {
        return [
            'class implementing all members' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Clazz(ArrayIterator::class),
                true,
            ],
            'class implementing only some members' => [
                new Intersection(new Clazz(Countable::class), new Clazz(ArrayAccess::class)),
                new Clazz(stdClass::class),
                false,
            ],
            'class implementing one of two members' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Clazz(Countable::class),
                false,
            ],
            'single member clazz' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Clazz(Traversable::class),
                false,
            ],
            'same intersection' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                true,
            ],
            'superset intersection' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Intersection(
                    new Clazz(Countable::class),
                    new Clazz(Traversable::class),
                    new Clazz(ArrayAccess::class),
                ),
                true,
            ],
            'subset intersection' => [
                new Intersection(
                    new Clazz(Countable::class),
                    new Clazz(Traversable::class),
                    new Clazz(ArrayAccess::class),
                ),
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                false,
            ],
            'partially overlapping intersection' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Intersection(new Clazz(Countable::class), new Clazz(ArrayAccess::class)),
                false,
            ],
            'disjoint intersection' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Intersection(new Clazz(ArrayAccess::class), new Clazz(stdClass::class)),
                false,
            ],
            'never' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Primitive(Primitive::NEVER),
                true,
            ],
            'object' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Primitive(Primitive::OBJECT),
                false,
            ],
            'mixed' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Primitive(Primitive::MIXED),
                false,
            ],
            'int' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Primitive(Primitive::INT),
                false,
            ],
            'union of accepted members' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Union(new Clazz(ArrayIterator::class), new Clazz(SplObjectStorage::class)),
                true,
            ],
            'union with a rejected member' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Union(new Clazz(ArrayIterator::class), new Clazz(stdClass::class)),
                false,
            ],
        ];
    }

    /** @return array<string, array{Intersection, mixed, bool}> */
    public static function provideAcceptsVar(): array
    {
        $countableTraversable = new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class));

        return [
            'instance of every member' => [$countableTraversable, new ArrayIterator([]), true],
            'instance of only one member' => [
                $countableTraversable,
                (static function () {
                    yield 1;
                })(),
                false,
            ],
            'instance of no member' => [$countableTraversable, new stdClass(), false],
            'null' => [$countableTraversable, null, false],
            'int' => [$countableTraversable, 1, false],
            'class name string' => [$countableTraversable, Countable::class, false],
            'array' => [$countableTraversable, [], false],
        ];
    }

    /** @return array<string, array{list<string>, string}> */
    public static function provideToString(): array
    {
        return [
            'already sorted' => [[Countable::class, Traversable::class], 'Countable&Traversable'],
            'reversed' => [[Traversable::class, Countable::class], 'Countable&Traversable'],
            'three members' => [
                [Traversable::class, Countable::class, ArrayAccess::class],
                'ArrayAccess&Countable&Traversable',
            ],
            'class and interface' => [[stdClass::class, ArrayObject::class], 'ArrayObject&stdClass'],
        ];
    }

    /** @return array<string, array{Intersection, object, bool}> */
    public static function provideEquals(): array
    {
        $intersection = new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class));

        return [
            'same instance' => [$intersection, $intersection, true],
            'same members same order' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                true,
            ],
            'same members different order' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Intersection(new Clazz(Traversable::class), new Clazz(Countable::class)),
                true,
            ],
            'same members different spelling' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Intersection(new Clazz('countable'), new Clazz('TRAVERSABLE')),
                true,
            ],
            'different members' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Intersection(new Clazz(Countable::class), new Clazz(ArrayAccess::class)),
                false,
            ],
            'subset' => [
                new Intersection(
                    new Clazz(Countable::class),
                    new Clazz(Traversable::class),
                    new Clazz(ArrayAccess::class),
                ),
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                false,
            ],
            'superset' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Intersection(
                    new Clazz(Countable::class),
                    new Clazz(Traversable::class),
                    new Clazz(ArrayAccess::class),
                ),
                false,
            ],
            'single clazz' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Clazz(Countable::class),
                false,
            ],
            'non type object' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new stdClass(),
                false,
            ],
        ];
    }

    /** @return array<string, array{Intersection, Intersection, bool}> */
    public static function provideHash(): array
    {
        return [
            'same members same order' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                true,
            ],
            'same members different order' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Intersection(new Clazz(Traversable::class), new Clazz(Countable::class)),
                true,
            ],
            'same members different spelling' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Intersection(new Clazz('countable'), new Clazz('TRAVERSABLE')),
                true,
            ],
            'different members' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Intersection(new Clazz(Countable::class), new Clazz(ArrayAccess::class)),
                false,
            ],
            'superset' => [
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                new Intersection(
                    new Clazz(Countable::class),
                    new Clazz(Traversable::class),
                    new Clazz(ArrayAccess::class),
                ),
                false,
            ],
        ];
    }

    /** @return array<string, array{list<Type>, string}> */
    public static function provideFormat(): array
    {
        return [
            'classes in given order' => [
                [new Clazz(Traversable::class), new Clazz(Countable::class)],
                'Traversable&Countable',
            ],
            'union parenthesized' => [
                [new Union(new Clazz(Traversable::class), new Primitive('array')), new Clazz(Countable::class)],
                '(Traversable|array)&Countable',
            ],
            'primitive member' => [[new Clazz(Countable::class), new Primitive('int')], 'Countable&int'],
            'single member' => [[new Clazz(Countable::class)], 'Countable'],
        ];
    }
}
