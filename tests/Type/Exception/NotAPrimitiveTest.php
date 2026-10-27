<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Type\Exception;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Type\Exception\InvalidType;
use Vivarium\Type\Exception\NotAPrimitive;

#[CoversClass(NotAPrimitive::class)]
final class NotAPrimitiveTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new NotAPrimitive('integer');

        self::assertInstanceOf(InvalidType::class, $exception);
        self::assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    public function testGetType(): void
    {
        self::assertSame('integer', (new NotAPrimitive('integer'))->getType());
    }

    #[DataProvider('provideMessages')]
    public function testGetMessage(string $type, string $message): void
    {
        self::assertSame($message, (new NotAPrimitive($type))->getMessage());
    }

    /** @return array<string, array{string, string}> */
    public static function provideMessages(): array
    {
        return [
            'alias' => ['integer', 'Expected "integer" to be a primitive type.'],
            'class name' => ['Countable', 'Expected "Countable" to be a primitive type.'],
            'empty' => ['', 'Expected "" to be a primitive type.'],
        ];
    }
}
