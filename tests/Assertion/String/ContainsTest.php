<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\String;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\String\Contains;

use function preg_quote;

#[CoversClass(Contains::class)]
final class ContainsTest extends TestCase
{
    /** @param Assertion<mixed> $assertion */
    #[DataProvider('provideValues')]
    public function testCheck(Assertion $assertion, mixed $value, string|null $message): void
    {
        self::assertSame($message === null, $assertion->check($value));
    }

    /** @param Assertion<mixed> $assertion */
    #[DataProvider('provideValues')]
    public function testAssert(Assertion $assertion, mixed $value, string|null $message): void
    {
        if ($message === null) {
            $this->expectNotToPerformAssertions();
        }

        if ($message !== null) {
            $this->expectException(AssertionFailed::class);
            $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');
        }

        $assertion->assert($value);
    }

    #[TestWith(['world', 'Custom string "world".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new Contains('lo'))->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new Contains('lo');

        return [
            'accepts hello' => [$assertion, 'hello', null],
            'accepts lo' => [$assertion, 'lo', null],
            'multibyte' => [new Contains('é'), 'café', null],
            'empty needle' => [new Contains(''), 'anything', null],
            'rejects world' => [$assertion, 'world', 'Expected value to contain "lo". Got string "world".'],
            'int' => [$assertion, 1, 'Expected value to be string. Got int 1.'],
        ];
    }
}
