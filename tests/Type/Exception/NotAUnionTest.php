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
        $exception = new NotAUnion($members, $reason, ...$parameters);

        self::assertSame($message, $exception->getMessage());
        self::assertSame($message, $exception->getFailure()->format());
    }

    /** @return array<string, array{list<Type>, string, array<string, string>, string}> */
    public static function provideMessages(): array
    {
        $intersection = new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class));

        return [
            'members in given order' => [
                [new Primitive('string'), new Primitive('int'), new Primitive('string')],
                'duplicate type {member}',
                ['member' => 'string'],
                'Expected a valid union: duplicate type "string". Got string "string|int|string".',
            ],
            'intersection parenthesized' => [
                [$intersection, new Clazz(Countable::class)],
                '{intersection} is more restrictive than {other}',
                ['intersection' => '(Countable&Traversable)', 'other' => 'Countable'],
                'Expected a valid union: "(Countable&Traversable)" is more restrictive than "Countable".'
                    . ' Got string "(Countable&Traversable)|Countable".',
            ],
            'no members' => [
                [],
                'a union needs at least two members',
                [],
                'Expected a valid union: a union needs at least two members. Got string "".',
            ],
        ];
    }
}
