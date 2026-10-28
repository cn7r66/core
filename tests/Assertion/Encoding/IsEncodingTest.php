<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Encoding;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\Encoding\IsEncoding;
use Vivarium\Assertion\Exception\AssertionFailed;

use function preg_quote;

#[CoversClass(IsEncoding::class)]
final class IsEncodingTest extends TestCase
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

    #[TestWith(['nope', 'Custom string "nope".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsEncoding())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new IsEncoding();

        return [
            'UTF-8' => [$assertion, 'UTF-8', null],
            'utf-8' => [$assertion, 'utf-8', null],
            'utf8' => [$assertion, 'utf8', null],
            'latin1' => [$assertion, 'latin1', null],
            'UTF-16' => [$assertion, 'UTF-16', null],
            'unknown' => [$assertion, 'nope', 'Expected value to be an encoding known to mbstring. Got string "nope".'],
            'empty' => [$assertion, '', 'Expected value to be an encoding known to mbstring. Got string "".'],
            'int' => [$assertion, 42, 'Expected value to be string. Got int 42.'],
        ];
    }
}
