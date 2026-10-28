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
use Vivarium\Assertion\Class\IsClass;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Test\Assertion\Stub\StubChild;
use Vivarium\Test\Assertion\Stub\StubContractA;
use Vivarium\Test\Assertion\Stub\StubEnum;
use Vivarium\Test\Assertion\Stub\StubMixin;
use Vivarium\Test\Assertion\Stub\StubParent;

use function preg_quote;
use function strtolower;

#[CoversClass(IsClass::class)]
final class IsClassTest extends TestCase
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

    #[TestWith(['Nope', 'Custom string "Nope".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsClass())->assert($value, 'Custom {value}.');
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new IsClass();

        return [
            'final class' => [$assertion, StubChild::class, null],
            'abstract class' => [$assertion, StubParent::class, null],
            'enum' => [$assertion, StubEnum::class, null],
            'lowercase' => [$assertion, strtolower(StubChild::class), null],
            'leading backslash' => [$assertion, '\\' . StubChild::class, null],
            'interface' => [
                $assertion,
                StubContractA::class,
                'Expected value to be the name of an existing class. Got string "' . StubContractA::class . '".',
            ],
            'trait' => [
                $assertion,
                StubMixin::class,
                'Expected value to be the name of an existing class. Got string "' . StubMixin::class . '".',
            ],
            'unknown' => [
                $assertion,
                'Nope',
                'Expected value to be the name of an existing class. Got string "Nope".',
            ],
            'int' => [$assertion, 1, 'Expected value to be string. Got int 1.'],
        ];
    }
}
