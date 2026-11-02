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
use Vivarium\Type\Exception\NotAType;

use function fclose;
use function fopen;

#[CoversClass(NotAType::class)]
final class NotATypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new NotAType('int|', 'it has an empty part');

        self::assertInstanceOf(InvalidType::class, $exception);
        self::assertInstanceOf(Failed::class, $exception);
        self::assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    /** @param array<string, string> $parameters */
    #[DataProvider('provideMessages')]
    public function testGetMessage(string $type, string $reason, array $parameters, string $message): void
    {
        $exception = new NotAType($type, $reason, ...$parameters);

        self::assertSame($message, $exception->getMessage());
        self::assertSame($message, $exception->getFailure()->format());
    }

    /** @return array<string, array{string, string, array<string, string>, string}> */
    public static function provideMessages(): array
    {
        return [
            'syntax' => [
                'int|',
                'it has an empty part',
                [],
                'Expected a valid type: it has an empty part. Got string "int|".',
            ],
            'reason parameter' => [
                '?self',
                '{keyword} cannot be resolved without a class',
                ['keyword' => 'self'],
                'Expected a valid type: "self" cannot be resolved without a class. Got string "?self".',
            ],
            'empty input' => [
                '',
                'it has an empty part',
                [],
                'Expected a valid type: it has an empty part. Got string "".',
            ],
        ];
    }

    public function testGetMessageOfResource(): void
    {
        $resource = fopen('php://memory', 'r');
        self::assertNotFalse($resource);

        self::assertSame(
            'Expected a valid type: resources have no type. Got resource (stream).',
            (new NotAType($resource, 'resources have no type'))->getMessage(),
        );

        fclose($resource);
    }
}
