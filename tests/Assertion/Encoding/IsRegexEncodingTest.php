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
use Vivarium\Assertion\Encoding\IsRegexEncoding;
use Vivarium\Assertion\Exception\AssertionFailed;

use function mb_regex_encoding;
use function preg_quote;

#[CoversClass(IsRegexEncoding::class)]
final class IsRegexEncodingTest extends TestCase
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

        (new IsRegexEncoding())->assert($value, 'Custom {value}.');
    }

    #[TestWith(['UTF-16'])]
    #[TestWith(['nope'])]
    public function testCheckRestoresRegexEncoding(string $value): void
    {
        $before = mb_regex_encoding();

        (new IsRegexEncoding())->check($value);

        self::assertSame($before, mb_regex_encoding());
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new IsRegexEncoding();

        return [
            'UTF-8' => [$assertion, 'UTF-8', null],
            'utf8' => [$assertion, 'utf8', null],
            'UTF-16' => [$assertion, 'UTF-16', null],
            'known to mbstring only' => [
                $assertion,
                'latin1',
                'Expected value to be an encoding supported by mbstring regex functions. Got string "latin1".',
            ],
            'unknown' => [
                $assertion,
                'nope',
                'Expected value to be an encoding supported by mbstring regex functions. Got string "nope".',
            ],
            'empty' => [
                $assertion,
                '',
                'Expected value to be an encoding supported by mbstring regex functions. Got string "".',
            ],
            'int' => [$assertion, 42, 'Expected value to be string. Got int 42.'],
        ];
    }
}
