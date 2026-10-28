<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Equality;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Equality\Equality;
use Vivarium\Equality\HashBuilder;
use Vivarium\Test\Equality\Stub\EqualityStub;

use function fclose;
use function fopen;
use function hrtime;
use function range;

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

    #[DataProvider('provideIdentities')]
    public function testAppendSameValue(mixed $value): void
    {
        self::assertSame(
            (new HashBuilder())->append($value)->getHashCode(),
            (new HashBuilder())->append($value)->getHashCode(),
        );
    }

    public function testAppendEmptyArray(): void
    {
        self::assertNotSame(
            (new HashBuilder())->append(1)->getHashCode(),
            (new HashBuilder())->append(1)->append([])->getHashCode(),
        );
    }

    public function testAppendLargeArray(): void
    {
        $start = hrtime(true);

        (new HashBuilder())->append(range(1, 200_000));

        self::assertLessThan(2_000_000_000, hrtime(true) - $start);
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
            'nested array boundary' => [[[1], 2], [[1, 2]]],
            'equality hash spelled like a scalar' => [
                new class implements Equality {
                    #[Override]
                    public function equals(object $object): bool
                    {
                        return false;
                    }

                    #[Override]
                    public function hash(): string
                    {
                        return 'i:1;';
                    }
                },
                1,
            ],
            'different resources' => [fopen('php://memory', 'r'), fopen('php://memory', 'r')],
            'resource and zero' => [fopen('php://memory', 'r'), 0],
        ];
    }

    /** @return array<string, array{mixed}> */
    public static function provideIdentities(): array
    {
        $closed = fopen('php://memory', 'r');
        if ($closed !== false) {
            fclose($closed);
        }

        return [
            'plain object' => [new stdClass()],
            'closure' => [static fn (): int => 1],
            'anonymous class' => [
                new class {
                },
            ],
            'equality object' => [new EqualityStub()],
            'resource' => [fopen('php://memory', 'r')],
            'closed resource' => [$closed],
        ];
    }
}
