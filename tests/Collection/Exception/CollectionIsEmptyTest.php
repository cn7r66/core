<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Collection\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnderflowException;
use Vivarium\Collection\Exception\CollectionIsEmpty;
use Vivarium\Collection\Exception\CollectionOperationFailed;
use Vivarium\Collection\Queue\ArrayQueue;
use Vivarium\Collection\Stack\ArrayStack;
use Vivarium\Failure\Failed;

#[CoversClass(CollectionIsEmpty::class)]
final class CollectionIsEmptyTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new CollectionIsEmpty('pop', new ArrayStack());

        self::assertInstanceOf(CollectionOperationFailed::class, $exception);
        self::assertInstanceOf(Failed::class, $exception);
        self::assertInstanceOf(UnderflowException::class, $exception);
    }

    #[DataProvider('provideMessages')]
    public function testGetMessage(string $operation, object $collection, string $message): void
    {
        $exception = new CollectionIsEmpty($operation, $collection);

        self::assertSame($message, $exception->getMessage());
        self::assertSame($message, $exception->getFailure()->format());
    }

    /** @return array<string, array{string, object, string}> */
    public static function provideMessages(): array
    {
        return [
            'stack' => [
                'pop',
                new ArrayStack(),
                'Expected an element to pop. Got object ' . ArrayStack::class . ' with no elements.',
            ],
            'queue' => [
                'dequeue',
                new ArrayQueue(),
                'Expected an element to dequeue. Got object ' . ArrayQueue::class . ' with no elements.',
            ],
        ];
    }
}
