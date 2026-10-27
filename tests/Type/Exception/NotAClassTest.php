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
        self::assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    public function testGetClass(): void
    {
        self::assertSame('Nope', (new NotAClass('Nope'))->getClass());
    }

    #[DataProvider('provideMessages')]
    public function testGetMessage(string $class, string $message): void
    {
        self::assertSame($message, (new NotAClass($class))->getMessage());
    }

    /** @return array<string, array{string, string}> */
    public static function provideMessages(): array
    {
        return [
            'missing class' => ['Nope', 'Expected "Nope" to be an existing class, interface or enum.'],
            'trait' => [
                StubReflectionShared::class,
                'Expected "' . StubReflectionShared::class . '" to be a class, interface or enum. Got a trait.',
            ],
            'empty' => ['', 'Expected "" to be an existing class, interface or enum.'],
        ];
    }
}
