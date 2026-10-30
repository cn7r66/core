<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Comparator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Comparator\ByPriority;
use Vivarium\Comparator\Prioritized;
use Vivarium\Comparator\Priority;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

#[CoversClass(ByPriority::class)]
final class ByPriorityTest extends TestCase
{
    #[DataProvider('providePriorities')]
    public function testCompare(int $firstPriority, int $secondPriority, int $sign): void
    {
        $comparator = new ByPriority();
        $first      = $this->prioritized($firstPriority);
        $second     = $this->prioritized($secondPriority);

        self::assertSame($sign, $comparator->compare($first, $second) <=> 0);
        self::assertSame(-$sign, $comparator->compare($second, $first) <=> 0);
    }

    #[DataProvider('provideNotPrioritized')]
    public function testCompareRejectsNotPrioritizedFirst(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessage($message);

        // @phpstan-ignore argument.type
        (new ByPriority())->compare($value, $this->prioritized(Priority::NORMAL));
    }

    #[DataProvider('provideNotPrioritized')]
    public function testCompareRejectsNotPrioritizedSecond(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessage($message);

        // @phpstan-ignore argument.type
        (new ByPriority())->compare($this->prioritized(Priority::NORMAL), $value);
    }

    /** @return array<string, array{mixed, string}> */
    public static function provideNotPrioritized(): array
    {
        return [
            'int' => [Priority::NORMAL, 'Expected value to be object.'],
            'plain object' => [new stdClass(), 'Expected value to be an instance of'],
            'getPriority without Prioritized' => [
                new class {
                    public function getPriority(): int
                    {
                        return Priority::NORMAL;
                    }
                },
                'Expected value to be an instance of',
            ],
        ];
    }

    /** @return array<string, array{int, int, int}> */
    public static function providePriorities(): array
    {
        return [
            'lower first' => [Priority::LOW, Priority::HIGH, -1],
            'equal' => [Priority::NORMAL, Priority::NORMAL, 0],
            'negative before zero' => [-1, 0, -1],
            'extremes' => [PHP_INT_MIN, PHP_INT_MAX, -1],
            'max after negative' => [PHP_INT_MAX, -1, 1],
            'min before positive' => [PHP_INT_MIN, 1, -1],
        ];
    }

    private function prioritized(int $priority): Prioritized
    {
        $prioritized = self::createStub(Prioritized::class);
        $prioritized->method('getPriority')->willReturn($priority);

        return $prioritized;
    }
}
