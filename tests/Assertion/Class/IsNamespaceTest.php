<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Class;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\Class\IsNamespace;
use Vivarium\Assertion\Exception\AssertionFailed;

use function preg_quote;

#[CoversClass(IsNamespace::class)]
final class IsNamespaceTest extends TestCase
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

    #[TestWith(['1App', 'Custom string "1App".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsNamespace())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new IsNamespace();

        return [
            'single segment' => [$assertion, 'App', null],
            'qualified' => [$assertion, 'App\\Foo\\Bar', null],
            'reserved word segment' => [$assertion, 'App\\Class', null],
            'non-ascii' => [$assertion, 'Ünï\\x', null],
            'leading backslash' => [
                $assertion,
                '\\App',
                'Expected value to be a valid namespace name. Got string "\\App".',
            ],
            'trailing backslash' => [
                $assertion,
                'App\\',
                'Expected value to be a valid namespace name. Got string "App\\".',
            ],
            'leading digit' => [
                $assertion,
                '1App',
                'Expected value to be a valid namespace name. Got string "1App".',
            ],
            'empty' => [
                $assertion,
                '',
                'Expected value to be a valid namespace name. Got string "".',
            ],
            'trailing newline' => [
                $assertion,
                "App\n",
                'Expected value to be a valid namespace name. Got string "App\\n".',
            ],
            'int' => [$assertion, 1, 'Expected value to be string. Got int 1.'],
        ];
    }
}
