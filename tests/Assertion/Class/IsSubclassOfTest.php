<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Class;

use ArrayObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\Class\IsSubclassOf;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Test\Assertion\Stub\StubChild;
use Vivarium\Test\Assertion\Stub\StubContractA;
use Vivarium\Test\Assertion\Stub\StubMixin;
use Vivarium\Test\Assertion\Stub\StubParent;

use function preg_quote;

#[CoversClass(IsSubclassOf::class)]
final class IsSubclassOfTest extends TestCase
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

    #[TestWith(['ArrayObject', 'Custom string "ArrayObject".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsSubclassOf(StubParent::class))->assert($value, 'Custom {value}.');
    }

    /** @param class-string $argument */
    #[TestWith(['Nope', 'Expected value to be the name of an existing class or interface. Got string "Nope".'])]
    public function testConstructorRejectsUnknownClass(string $argument, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        new IsSubclassOf($argument);
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new IsSubclassOf(StubParent::class);

        return [
            'child' => [$assertion, StubChild::class, null],
            'implementing class' => [new IsSubclassOf(StubContractA::class), StubParent::class, null],
            'itself' => [
                $assertion,
                StubParent::class,
                'Expected value to be a subclass of "'
                    . StubParent::class
                    . '". Got string "'
                    . StubParent::class
                    . '".',
            ],
            'unrelated' => [
                $assertion,
                ArrayObject::class,
                'Expected value to be a subclass of "' . StubParent::class . '". Got string "ArrayObject".',
            ],
            'trait' => [
                $assertion,
                StubMixin::class,
                'Expected value to be the name of an existing class or interface. Got string "'
                    . StubMixin::class
                    . '".',
            ],
        ];
    }
}
