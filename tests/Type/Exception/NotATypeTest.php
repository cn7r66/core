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
use Vivarium\Type\Exception\NotAType;

#[CoversClass(NotAType::class)]
final class NotATypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new NotAType('int|', 'empty type');

        self::assertInstanceOf(InvalidType::class, $exception);
        self::assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    public function testGetType(): void
    {
        self::assertSame('int|', (new NotAType('int|', 'empty type'))->getType());
    }

    public function testGetReason(): void
    {
        self::assertSame('empty type', (new NotAType('int|', 'empty type'))->getReason());
    }

    #[DataProvider('provideMessages')]
    public function testGetMessage(string $type, string $reason, string $message): void
    {
        self::assertSame($message, (new NotAType($type, $reason))->getMessage());
    }

    /** @return array<string, array{string, string, string}> */
    public static function provideMessages(): array
    {
        return [
            'syntax' => ['int|', 'empty type', 'Expected "int|" to be a type: empty type.'],
            'contextual keyword' => [
                'self',
                'self cannot be resolved without a class',
                'Expected "self" to be a type: self cannot be resolved without a class.',
            ],
            'empty input' => ['', 'empty type', 'Expected "" to be a type: empty type.'],
        ];
    }
}
