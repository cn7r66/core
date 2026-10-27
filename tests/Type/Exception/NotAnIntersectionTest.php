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
use Vivarium\Type\Exception\NotAnIntersection;
use Vivarium\Type\Primitive;
use Vivarium\Type\Type;
use Vivarium\Type\Union;

#[CoversClass(NotAnIntersection::class)]
final class NotAnIntersectionTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new NotAnIntersection([new Clazz(Countable::class)], 'an intersection needs at least two members');

        self::assertInstanceOf(InvalidType::class, $exception);
        self::assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    public function testGetMembers(): void
    {
        $members = [new Clazz(Countable::class), new Clazz(Countable::class)];

        self::assertSame($members, (new NotAnIntersection($members, 'duplicate type Countable'))->getMembers());
    }

    public function testGetReason(): void
    {
        $exception = new NotAnIntersection(
            [new Clazz(Countable::class), new Clazz(Countable::class)],
            'duplicate type Countable',
        );

        self::assertSame('duplicate type Countable', $exception->getReason());
    }

    /** @param list<Type> $members */
    #[DataProvider('provideMessages')]
    public function testGetMessage(array $members, string $reason, string $message): void
    {
        self::assertSame($message, (new NotAnIntersection($members, $reason))->getMessage());
    }

    /** @return array<string, array{list<Type>, string, string}> */
    public static function provideMessages(): array
    {
        return [
            'members in given order' => [
                [new Clazz(Traversable::class), new Clazz(Countable::class), new Clazz(Traversable::class)],
                'duplicate type Traversable',
                'Expected "Traversable&Countable&Traversable" to be a valid intersection: duplicate type Traversable.',
            ],
            'union parenthesized' => [
                [new Union(new Clazz(Traversable::class), new Primitive('array')), new Clazz(Countable::class)],
                'Traversable|array cannot be part of an intersection type',
                'Expected "(Traversable|array)&Countable" to be a valid intersection:'
                    . ' Traversable|array cannot be part of an intersection type.',
            ],
            'primitive member' => [
                [new Clazz(Countable::class), new Primitive('int')],
                'int cannot be part of an intersection type',
                'Expected "Countable&int" to be a valid intersection: int cannot be part of an intersection type.',
            ],
            'no members' => [
                [],
                'an intersection needs at least two members',
                'Expected "" to be a valid intersection: an intersection needs at least two members.',
            ],
        ];
    }
}
