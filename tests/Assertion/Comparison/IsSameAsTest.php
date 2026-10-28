<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Comparison;

use ArrayObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Comparison\IsSameAs;
use Vivarium\Assertion\Exception\AssertionFailed;

use function preg_quote;

#[CoversClass(IsSameAs::class)]
final class IsSameAsTest extends TestCase
{
    #[DataProvider('provideValues')]
    public function testCheck(mixed $compare, mixed $value, string|null $message): void
    {
        self::assertSame($message === null, (new IsSameAs($compare))->check($value));
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

        (new IsSameAs($compare))->assert($value);
    }

    #[TestWith([5, 6, 'Custom int 6 against 5.'])]
    public function testAssertWithCustomMessage(mixed $compare, mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsSameAs($compare))->assert($value, 'Custom {value} against {compare}.');
    }

    /** @return array<string, array{mixed, mixed, string|null}> */
    public static function provideValues(): array
    {
        $object = new stdClass();

        return [
            'int' => [5, 5, null],
            'string' => ['a', 'a', null],
            'same instance' => [$object, $object, null],
            'same list' => [[1, 2], [1, 2], null],
            'other int' => [5, 6, 'Expected value to be the same as 5. Got int 6.'],
            'int and float' => [1, 1.0, 'Expected value to be the same as 1. Got float 1.0.'],
            'numeric string and int' => ['1', 1, 'Expected value to be the same as "1". Got int 1.'],
            'list order' => [[1, 2], [2, 1], 'Expected value to be the same as [1, 2]. Got array(2) [2, 1].'],
            'another instance' => [
                $object,
                new stdClass(),
                'Expected value to be the same instance as stdClass. Got another instance.',
            ],
            'instance of another class' => [
                $object,
                new ArrayObject(),
                'Expected value to be the same as stdClass. Got object ArrayObject.',
            ],
        ];
    }
}
