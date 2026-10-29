<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Comparison;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Comparison\IsEqualTo;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Test\Assertion\Stub\StubEquality;
use Vivarium\Test\Assertion\Stub\StubEqualToAnything;

use function preg_quote;

use const NAN;

#[CoversClass(IsEqualTo::class)]
final class IsEqualToTest extends TestCase
{
    #[DataProvider('provideValues')]
    public function testCheck(mixed $compare, mixed $value, string|null $message): void
    {
        self::assertSame($message === null, (new IsEqualTo($compare))->check($value));
    }

    #[DataProvider('provideValues')]
    public function testAssert(mixed $compare, mixed $value, string|null $message): void
    {
        if ($message === null) {
            $this->expectNotToPerformAssertions();
        }

        if ($message !== null) {
            $this->expectException(AssertionFailed::class);
            $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');
        }

        (new IsEqualTo($compare))->assert($value);
    }

    #[TestWith([5, 6, 'Custom int 6 against 5.'])]
    public function testAssertWithCustomMessage(mixed $compare, mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsEqualTo($compare))->assert($value, 'Custom {value} against {compare}.');
    }

    /** @return array<string, array{mixed, mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'int' => [5, 5, null],
            'list' => [[1, 2], [1, 2], null],
            'map' => [['a' => 1, 'b' => 2], ['a' => 1, 'b' => 2], null],
            'float not a number' => [NAN, NAN, null],
            'equality objects' => [new StubEquality(), new StubEquality(), null],
            'other int' => [5, 6, 'Expected value to be equal to 5. Got int 6.'],
            'int and float' => [1, 1.0, 'Expected value to be equal to 1. Got float 1.0.'],
            'map order' => [
                ['a' => 1, 'b' => 2],
                ['b' => 2, 'a' => 1],
                'Expected value to be equal to [1, 2]. Got array(2) [2, 1].',
            ],
            'float sum' => [
                0.3,
                0.1 + 0.2,
                'Expected value to be equal to 0.3. Got float 0.30000000000000004.',
            ],
            'equality objects that disagree' => [
                new StubEquality(),
                new StubEqualToAnything(),
                'Expected value to be equal to ' . StubEquality::class
                    . '. Got object ' . StubEqualToAnything::class . '.',
            ],
        ];
    }
}
