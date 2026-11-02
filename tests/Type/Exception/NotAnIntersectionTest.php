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
use Vivarium\Failure\Failed;
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
        self::assertInstanceOf(Failed::class, $exception);
        self::assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    /**
     * @param list<Type>            $members
     * @param array<string, string> $parameters
     */
    #[DataProvider('provideMessages')]
    public function testGetMessage(array $members, string $reason, array $parameters, string $message): void
    {
        $exception = new NotAnIntersection($members, $reason, ...$parameters);

        self::assertSame($message, $exception->getMessage());
        self::assertSame($message, $exception->getFailure()->format());
    }

    /** @return array<string, array{list<Type>, string, array<string, string>, string}> */
    public static function provideMessages(): array
    {
        return [
            'members in given order' => [
                [new Clazz(Traversable::class), new Clazz(Countable::class), new Clazz(Traversable::class)],
                'duplicate type {member}',
                ['member' => 'Traversable'],
                'Expected a valid intersection: duplicate type "Traversable".'
                    . ' Got string "Traversable&Countable&Traversable".',
            ],
            'union parenthesized' => [
                [new Union(new Clazz(Traversable::class), new Primitive('array')), new Clazz(Countable::class)],
                '{member} cannot be part of an intersection',
                ['member' => 'Traversable|array'],
                'Expected a valid intersection: "Traversable|array" cannot be part of an intersection.'
                    . ' Got string "(Traversable|array)&Countable".',
            ],
            'primitive member' => [
                [new Clazz(Countable::class), new Primitive('int')],
                '{member} cannot be part of an intersection',
                ['member' => 'int'],
                'Expected a valid intersection: "int" cannot be part of an intersection. Got string "Countable&int".',
            ],
            'no members' => [
                [],
                'an intersection needs at least two members',
                [],
                'Expected a valid intersection: an intersection needs at least two members. Got string "".',
            ],
        ];
    }
}
