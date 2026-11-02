<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Collection\Exception;

use OutOfBoundsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Collection\Exception\CollectionOperationFailed;
use Vivarium\Collection\Exception\KeyNotFound;
use Vivarium\Failure\Failed;

#[CoversClass(KeyNotFound::class)]
final class KeyNotFoundTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new KeyNotFound('missing');

        self::assertInstanceOf(CollectionOperationFailed::class, $exception);
        self::assertInstanceOf(Failed::class, $exception);
        self::assertInstanceOf(OutOfBoundsException::class, $exception);
    }

    #[DataProvider('provideMessages')]
    public function testGetMessage(mixed $key, string $message): void
    {
        $exception = new KeyNotFound($key);

        self::assertSame($message, $exception->getMessage());
        self::assertSame($message, $exception->getFailure()->format());
    }

    /** @return array<string, array{mixed, string}> */
    public static function provideMessages(): array
    {
        return [
            'string key' => ['missing', 'Expected a key present in the map. Got string "missing".'],
            'int key' => [42, 'Expected a key present in the map. Got int 42.'],
            'object key' => [new stdClass(), 'Expected a key present in the map. Got object stdClass.'],
        ];
    }
}
