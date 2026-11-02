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
use Vivarium\Failure\Failed;
use Vivarium\Type\Exception\InvalidType;
use Vivarium\Type\Exception\NotAPrimitive;

#[CoversClass(NotAPrimitive::class)]
final class NotAPrimitiveTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new NotAPrimitive('integer');

        self::assertInstanceOf(InvalidType::class, $exception);
        self::assertInstanceOf(Failed::class, $exception);
        self::assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    #[DataProvider('provideMessages')]
    public function testGetMessage(string $type, string $message): void
    {
        $exception = new NotAPrimitive($type);

        self::assertSame($message, $exception->getMessage());
        self::assertSame($message, $exception->getFailure()->format());
    }

    /** @return array<string, array{string, string}> */
    public static function provideMessages(): array
    {
        return [
            'alias' => ['integer', 'Expected a primitive type. Got string "integer".'],
            'class name' => ['Countable', 'Expected a primitive type. Got string "Countable".'],
            'empty' => ['', 'Expected a primitive type. Got string "".'],
        ];
    }
}
