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
use Vivarium\Test\Type\Stub\StubReflectionShared;
use Vivarium\Type\Exception\InvalidType;
use Vivarium\Type\Exception\NotAClass;

#[CoversClass(NotAClass::class)]
final class NotAClassTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new NotAClass('Nope');

        self::assertInstanceOf(InvalidType::class, $exception);
        self::assertInstanceOf(Failed::class, $exception);
        self::assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    #[DataProvider('provideMessages')]
    public function testGetMessage(string $class, string $message): void
    {
        $exception = new NotAClass($class);

        self::assertSame($message, $exception->getMessage());
        self::assertSame($message, $exception->getFailure()->format());
    }

    /** @return array<string, array{string, string}> */
    public static function provideMessages(): array
    {
        return [
            'missing class' => ['Nope', 'Expected an existing class, interface or enum. Got string "Nope".'],
            'trait' => [
                StubReflectionShared::class,
                'Expected a class, interface or enum, not a trait. Got string "'
                    . StubReflectionShared::class . '".',
            ],
            'empty' => ['', 'Expected an existing class, interface or enum. Got string "".'],
        ];
    }
}
