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
use Vivarium\Collection\Exception\CollectionOperationFailed;
use Vivarium\Collection\Exception\IndexOutOfBounds;
use Vivarium\Failure\Failed;

#[CoversClass(IndexOutOfBounds::class)]
final class IndexOutOfBoundsTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new IndexOutOfBounds(5, 3);

        self::assertInstanceOf(CollectionOperationFailed::class, $exception);
        self::assertInstanceOf(Failed::class, $exception);
        self::assertInstanceOf(OutOfBoundsException::class, $exception);
    }

    #[DataProvider('provideMessages')]
    public function testGetMessage(int $index, int $count, string $message): void
    {
        $exception = new IndexOutOfBounds($index, $count);

        self::assertSame($message, $exception->getMessage());
        self::assertSame($message, $exception->getFailure()->format());
    }

    /** @return array<string, array{int, int, string}> */
    public static function provideMessages(): array
    {
        return [
            'past the end' => [5, 3, 'Expected an index in [0, 3). Got int 5.'],
            'negative' => [-1, 3, 'Expected an index in [0, 3). Got int -1.'],
            'empty sequence' => [0, 0, 'Expected an index in [0, 0). Got int 0.'],
        ];
    }
}
