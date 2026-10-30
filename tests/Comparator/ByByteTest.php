<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Comparator;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Stringable;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Comparator\ByByte;

#[CoversClass(ByByte::class)]
final class ByByteTest extends TestCase
{
    #[DataProvider('provideStrings')]
    public function testCompare(string $first, string $second, int $sign): void
    {
        $comparator = new ByByte();

        self::assertSame($sign, $comparator->compare($first, $second) <=> 0);
        self::assertSame(-$sign, $comparator->compare($second, $first) <=> 0);
    }

    #[DataProvider('provideNotStrings')]
    public function testCompareRejectsNotStringFirst(mixed $value): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessage('Expected value to be string.');

        // @phpstan-ignore argument.type
        (new ByByte())->compare($value, 'a');
    }

    #[DataProvider('provideNotStrings')]
    public function testCompareRejectsNotStringSecond(mixed $value): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessage('Expected value to be string.');

        // @phpstan-ignore argument.type
        (new ByByte())->compare('a', $value);
    }

    /** @return array<string, array{mixed}> */
    public static function provideNotStrings(): array
    {
        return [
            'int' => [1],
            'null' => [null],
            'Stringable' => [
                new class implements Stringable {
                    #[Override]
                    public function __toString(): string
                    {
                        return 'a';
                    }
                },
            ],
        ];
    }

    /** @return array<string, array{string, string, int}> */
    public static function provideStrings(): array
    {
        return [
            'less' => ['a', 'z', -1],
            'equal' => ['a', 'a', 0],
            'empty before any' => ['', 'a', -1],
            'empty equal to empty' => ['', '', 0],
            'prefix first' => ['abc', 'abcd', -1],
            'uppercase before lowercase' => ['B', 'a', -1],
            'accent after ASCII' => ['z', 'é', -1],
            'binary' => ["\x00", "\xFF", -1],
            'UTF-8 in code point order' => ["\u{E000}", "\u{10000}", -1],
        ];
    }
}
