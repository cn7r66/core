<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Type\Exception;

use Countable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Traversable;
use Vivarium\Type\Clazz;
use Vivarium\Type\Exception\InvalidType;
use Vivarium\Type\Exception\NotAUnion;
use Vivarium\Type\Intersection;
use Vivarium\Type\Primitive;
use Vivarium\Type\Type;

#[CoversClass(NotAUnion::class)]
final class NotAUnionTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new NotAUnion([new Primitive('int')], 'a union needs at least two members');

        self::assertInstanceOf(InvalidType::class, $exception);
        self::assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    public function testGetMembers(): void
    {
        $members = [new Primitive('int'), new Primitive('int')];

        self::assertSame($members, (new NotAUnion($members, 'duplicate type int'))->getMembers());
    }

    public function testGetReason(): void
    {
        $exception = new NotAUnion([new Primitive('int'), new Primitive('int')], 'duplicate type int');

        self::assertSame('duplicate type int', $exception->getReason());
    }

    /** @param list<Type> $members */
    #[DataProvider('provideMessages')]
    public function testGetMessage(array $members, string $reason, string $message): void
    {
        self::assertSame($message, (new NotAUnion($members, $reason))->getMessage());
    }

    /** @return array<string, array{list<Type>, string, string}> */
    public static function provideMessages(): array
    {
        $intersection = new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class));

        return [
            'members in given order' => [
                [new Primitive('string'), new Primitive('int'), new Primitive('string')],
                'duplicate type string',
                'Expected "string|int|string" to be a valid union: duplicate type string.',
            ],
            'intersection parenthesized' => [
                [$intersection, new Clazz(Countable::class)],
                '(Countable&Traversable) is more restrictive than Countable',
                'Expected "(Countable&Traversable)|Countable" to be a valid union:'
                    . ' (Countable&Traversable) is more restrictive than Countable.',
            ],
            'no members' => [
                [],
                'a union needs at least two members',
                'Expected "" to be a valid union: a union needs at least two members.',
            ],
        ];
    }
}
