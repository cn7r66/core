<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Equality;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Equality\HashBuilder;
use Vivarium\Test\Equality\Stub\EqualityStub;

#[CoversClass(HashBuilder::class)]
final class HashBuilderTest extends TestCase
{
    #[DataProviderExternal(EqualsBuilderTest::class, 'provideEqual')]
    public function testGetHashCode(mixed $first, mixed $second): void
    {
        self::assertSame(
            (new HashBuilder())->append($first)->getHashCode(),
            (new HashBuilder())->append($second)->getHashCode(),
        );
    }

    #[DataProvider('provideDistinct')]
    public function testGetHashCodeDistinguishes(mixed $first, mixed $second): void
    {
        self::assertNotSame(
            (new HashBuilder())->append($first)->getHashCode(),
            (new HashBuilder())->append($second)->getHashCode(),
        );
    }

    #[DataProvider('provideObjects')]
    public function testAppendObject(object $object): void
    {
        self::assertSame(
            (new HashBuilder())->append($object)->getHashCode(),
            (new HashBuilder())->append($object)->getHashCode(),
        );
    }

    public function testAppendLeavesOriginalUnchanged(): void
    {
        $builder = new HashBuilder();
        $empty   = $builder->getHashCode();

        $builder->append(1);

        self::assertSame($empty, $builder->getHashCode());
    }

    /** @return array<string, array{mixed, mixed}> */
    public static function provideDistinct(): array
    {
        return [
            'int and numeric string' => [1, '1'],
            'int and float' => [1, 1.0],
            'list order' => [[1, 2], [2, 1]],
            'map order' => [['a' => 1, 'b' => 2], ['b' => 2, 'a' => 1]],
            'different instances' => [new stdClass(), new stdClass()],
        ];
    }

    /** @return array<string, array{object}> */
    public static function provideObjects(): array
    {
        return [
            'plain object' => [new stdClass()],
            'closure' => [static fn (): int => 1],
            'anonymous class' => [
                new class {
                },
            ],
            'equality object' => [new EqualityStub()],
        ];
    }
}
