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
use Countable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SplObjectStorage;
use stdClass;
use Traversable;
use Vivarium\Test\Type\Stub\StubType;
use Vivarium\Type\Clazz;
use Vivarium\Type\Exception\InvalidType;
use Vivarium\Type\Exception\NotAUnion;
use Vivarium\Type\Intersection;
use Vivarium\Type\Primitive;
use Vivarium\Type\Type;
use Vivarium\Type\Union;

use function array_map;

#[CoversClass(Union::class)]
final class UnionTest extends TestCase
{
    /** @param list<Type> $members */
    #[DataProvider('provideInvalidMembers')]
    public function testConstructorException(array $members, string $template, string $message): void
    {
        try {
            new Union(...$members);
        } catch (NotAUnion $e) {
            self::assertSame($template, $e->getFailure()->getTemplate());
            self::assertSame($message, $e->getMessage());
            self::assertInstanceOf(InvalidType::class, $e);
            self::assertInstanceOf(InvalidArgumentException::class, $e);

            return;
        }

        self::fail('NotAUnion was not thrown');
    }

    /**
     * @param list<Type>                         $members
     * @param list<Primitive|Clazz|Intersection> $expected
     */
    #[DataProvider('provideGetMembers')]
    public function testGetMembers(array $members, array $expected): void
    {
        $hash   = static fn (Type $member): string => $member->hash();
        $actual = array_map($hash, (new Union(...$members))->getMembers());

        self::assertSame(array_map($hash, $expected), $actual);
    }

    #[DataProvider('provideAccepts')]
    public function testAccepts(Union $target, Type $source, bool $expected): void
    {
        self::assertSame($expected, $target->accepts($source));
    }

    #[DataProvider('provideAcceptsVar')]
    public function testAcceptsVar(Union $target, mixed $value, bool $expected): void
    {
        self::assertSame($expected, $target->acceptsVar($value));
    }

    #[DataProvider('provideToString')]
    public function testToString(Union $union, string $expected): void
    {
        self::assertSame($expected, (string) $union);
    }

    #[DataProvider('provideEquals')]
    public function testEquals(Union $union, object $other, bool $expected): void
    {
        self::assertSame($expected, $union->equals($other));
    }

    #[DataProvider('provideHash')]
    public function testHash(Union $a, Union $b, bool $expectedEqual): void
    {
        if ($expectedEqual) {
            self::assertSame($a->hash(), $b->hash());

            return;
        }

        self::assertNotSame($a->hash(), $b->hash());
    }

    public function testConstructorExceptionFlattensMembers(): void
    {
        $int  = new Primitive('int');
        $null = new Primitive('null');

        try {
            new Union(new Union($int, $null), $int);
        } catch (NotAUnion $e) {
            self::assertSame(
                'Expected a valid union: duplicate type "int". Got string "int|null|int".',
                $e->getMessage(),
            );

            return;
        }

        self::fail('NotAUnion was not thrown');
    }

    /** @param list<Type> $members */
    #[DataProvider('provideFormat')]
    public function testFormat(array $members, string $expected): void
    {
        self::assertSame($expected, Union::format(...$members));
    }

    /** @return array<string, array{list<Type>, string, string}> */
    public static function provideInvalidMembers(): array
    {
        return [
            'foreign type' => [
                [new StubType(), new Primitive('int')],
                'Expected a valid union: {member} cannot be part of a union. Got {value}.',
                'Expected a valid union: "Stub" cannot be part of a union. Got string "Stub|int".',
            ],
            'no members' => [
                [],
                'Expected a valid union: a union needs at least two members. Got {value}.',
                'Expected a valid union: a union needs at least two members. Got string "".',
            ],
            'single member' => [
                [new Primitive('int')],
                'Expected a valid union: a union needs at least two members. Got {value}.',
                'Expected a valid union: a union needs at least two members. Got string "int".',
            ],
            'duplicate int' => [
                [new Primitive('int'), new Primitive('int')],
                'Expected a valid union: duplicate type {member}. Got {value}.',
                'Expected a valid union: duplicate type "int". Got string "int|int".',
            ],
            'duplicate null' => [
                [new Primitive('null'), new Primitive('null')],
                'Expected a valid union: duplicate type {member}. Got {value}.',
                'Expected a valid union: duplicate type "null". Got string "null|null".',
            ],
            'duplicate intersection in different order' => [
                [
                    new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                    new Intersection(new Clazz(Traversable::class), new Clazz(Countable::class)),
                ],
                'Expected a valid union: duplicate type {member}. Got {value}.',
                'Expected a valid union: duplicate type "(Countable&Traversable)".'
                    . ' Got string "(Countable&Traversable)|(Countable&Traversable)".',
            ],
            'mixed with int' => [
                [new Primitive('mixed'), new Primitive('int')],
                'Expected a valid union: {member} can only be used as a standalone type. Got {value}.',
                'Expected a valid union: "mixed" can only be used as a standalone type. Got string "mixed|int".',
            ],
            'never with int' => [
                [new Primitive('never'), new Primitive('int')],
                'Expected a valid union: {member} can only be used as a standalone type. Got {value}.',
                'Expected a valid union: "never" can only be used as a standalone type. Got string "never|int".',
            ],
            'void with int' => [
                [new Primitive('void'), new Primitive('int')],
                'Expected a valid union: {member} can only be used as a standalone type. Got {value}.',
                'Expected a valid union: "void" can only be used as a standalone type. Got string "void|int".',
            ],
            'bool with false' => [
                [new Primitive('bool'), new Primitive('false')],
                'Expected a valid union: {member} is redundant with bool. Got {value}.',
                'Expected a valid union: "false" is redundant with bool. Got string "bool|false".',
            ],
            'bool with true' => [
                [new Primitive('bool'), new Primitive('true')],
                'Expected a valid union: {member} is redundant with bool. Got {value}.',
                'Expected a valid union: "true" is redundant with bool. Got string "bool|true".',
            ],
            'true with false' => [
                [new Primitive('true'), new Primitive('false')],
                'Expected a valid union: true and false must be written as bool. Got {value}.',
                'Expected a valid union: true and false must be written as bool. Got string "true|false".',
            ],
            'object with class' => [
                [new Primitive('object'), new Clazz(Countable::class)],
                'Expected a valid union: object makes the class types redundant. Got {value}.',
                'Expected a valid union: object makes the class types redundant. Got string "object|Countable".',
            ],
            'object with intersection' => [
                [
                    new Primitive('object'),
                    new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                ],
                'Expected a valid union: object makes the class types redundant. Got {value}.',
                'Expected a valid union: object makes the class types redundant.'
                    . ' Got string "object|(Countable&Traversable)".',
            ],
            'intersection with one of its members' => [
                [
                    new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                    new Clazz(Countable::class),
                ],
                'Expected a valid union: {intersection} is more restrictive than {other}. Got {value}.',
                'Expected a valid union: "(Countable&Traversable)" is more restrictive than "Countable".'
                    . ' Got string "(Countable&Traversable)|Countable".',
            ],
            'intersection with a wider intersection' => [
                [
                    new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                    new Intersection(
                        new Clazz(Countable::class),
                        new Clazz(Traversable::class),
                        new Clazz(ArrayAccess::class),
                    ),
                ],
                'Expected a valid union: {intersection} is more restrictive than {other}. Got {value}.',
                'Expected a valid union: "(ArrayAccess&Countable&Traversable)" is more restrictive than'
                    . ' "(Countable&Traversable)".'
                    . ' Got string(59) "(Countable&Traversable)|(ArrayAccess&Countable&Tra"….',
            ],
        ];
    }

    /** @return array<string, array{list<Type>, list<Primitive|Clazz|Intersection>}> */
    public static function provideGetMembers(): array
    {
        return [
            'nested union flattened' => [
                [new Union(new Primitive('string'), new Primitive('int')), new Primitive('null')],
                [new Primitive('int'), new Primitive('null'), new Primitive('string')],
            ],
            'already sorted' => [
                [new Primitive('int'), new Primitive('string')],
                [new Primitive('int'), new Primitive('string')],
            ],
            'reversed primitives' => [
                [new Primitive('string'), new Primitive('int')],
                [new Primitive('int'), new Primitive('string')],
            ],
            'class before primitives' => [
                [new Primitive('null'), new Primitive('int'), new Clazz(Countable::class)],
                [new Clazz(Countable::class), new Primitive('int'), new Primitive('null')],
            ],
            'intersection before primitive' => [
                [
                    new Primitive('int'),
                    new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                ],
                [
                    new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                    new Primitive('int'),
                ],
            ],
        ];
    }

    /** @return array<string, array{Union, Type, bool}> */
    public static function provideAccepts(): array
    {
        return [
            'union accepts narrower union' => [
                new Union(new Primitive('float'), new Primitive('null')),
                new Union(new Primitive('int'), new Primitive('null')),
                true,
            ],
            'union rejects union with unaccepted member' => [
                new Union(new Primitive('int'), new Primitive('null')),
                new Union(new Primitive('int'), new Primitive('string')),
                false,
            ],
            'union accepts one of its members' => [
                new Union(new Primitive('int'), new Primitive('string')),
                new Primitive('int'),
                true,
            ],
            'union rejects unrelated primitive' => [
                new Union(new Primitive('int'), new Primitive('string')),
                new Primitive('float'),
                false,
            ],
            'union accepts subclass of a member' => [
                new Union(new Clazz(Countable::class), new Primitive('null')),
                new Clazz(ArrayIterator::class),
                true,
            ],
            'union with intersection accepts same intersection' => [
                new Union(
                    new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                    new Clazz(ArrayAccess::class),
                ),
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                true,
            ],
            'union with intersection accepts class implementing all of it' => [
                new Union(
                    new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                    new Clazz(ArrayAccess::class),
                ),
                new Clazz(SplObjectStorage::class),
                true,
            ],
            'union with intersection rejects one of its parts' => [
                new Union(
                    new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                    new Clazz(ArrayAccess::class),
                ),
                new Clazz(Countable::class),
                false,
            ],
            'union accepts never' => [
                new Union(new Primitive('int'), new Primitive('null')),
                new Primitive('never'),
                true,
            ],
        ];
    }

    /** @return array<string, array{Union, mixed, bool}> */
    public static function provideAcceptsVar(): array
    {
        return [
            'int|null accepts null' => [new Union(new Primitive('int'), new Primitive('null')), null, true],
            'int|null accepts int' => [new Union(new Primitive('int'), new Primitive('null')), 1, true],
            'int|null rejects string' => [new Union(new Primitive('int'), new Primitive('null')), 'x', false],
            'Countable|null accepts countable instance' => [
                new Union(new Clazz(Countable::class), new Primitive('null')),
                new ArrayIterator([]),
                true,
            ],
            'Countable|null rejects other object' => [
                new Union(new Clazz(Countable::class), new Primitive('null')),
                new stdClass(),
                false,
            ],
        ];
    }

    /** @return array<string, array{Union, string}> */
    public static function provideToString(): array
    {
        return [
            'primitives sorted' => [new Union(new Primitive('string'), new Primitive('int')), 'int|string'],
            'class and primitives sorted' => [
                new Union(new Primitive('null'), new Primitive('int'), new Clazz(Countable::class)),
                'Countable|int|null',
            ],
            'intersection in parentheses first' => [
                new Union(
                    new Primitive('int'),
                    new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                ),
                '(Countable&Traversable)|int',
            ],
            'false with null' => [new Union(new Primitive('null'), new Primitive('false')), 'false|null'],
        ];
    }

    /** @return array<string, array{Union, object, bool}> */
    public static function provideEquals(): array
    {
        $union = new Union(new Primitive('int'), new Primitive('string'));

        return [
            'same instance' => [$union, $union, true],
            'same order' => [
                new Union(new Primitive('int'), new Primitive('string')),
                new Union(new Primitive('int'), new Primitive('string')),
                true,
            ],
            'different order' => [
                new Union(new Primitive('int'), new Primitive('string')),
                new Union(new Primitive('string'), new Primitive('int')),
                true,
            ],
            'different member' => [
                new Union(new Primitive('int'), new Primitive('string')),
                new Union(new Primitive('int'), new Primitive('float')),
                false,
            ],
            'primitive member' => [
                new Union(new Primitive('int'), new Primitive('string')),
                new Primitive('int'),
                false,
            ],
            'stdClass' => [new Union(new Primitive('int'), new Primitive('string')), new stdClass(), false],
        ];
    }

    /** @return array<string, array{Union, Union, bool}> */
    public static function provideHash(): array
    {
        return [
            'same order' => [
                new Union(new Primitive('int'), new Primitive('string')),
                new Union(new Primitive('int'), new Primitive('string')),
                true,
            ],
            'different order' => [
                new Union(new Primitive('int'), new Primitive('string')),
                new Union(new Primitive('string'), new Primitive('int')),
                true,
            ],
            'different member' => [
                new Union(new Primitive('int'), new Primitive('string')),
                new Union(new Primitive('int'), new Primitive('float')),
                false,
            ],
        ];
    }

    /** @return array<string, array{list<Type>, string}> */
    public static function provideFormat(): array
    {
        return [
            'primitives in given order' => [[new Primitive('string'), new Primitive('int')], 'string|int'],
            'intersection parenthesized' => [
                [new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)), new Primitive('null')],
                '(Countable&Traversable)|null',
            ],
            'invalid list' => [[new Primitive('int'), new Primitive('int')], 'int|int'],
            'single member' => [[new Primitive('int')], 'int'],
            'no members' => [[], ''],
        ];
    }
}
