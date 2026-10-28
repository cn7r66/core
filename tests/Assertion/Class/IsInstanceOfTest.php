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
use Vivarium\Assertion\Class\IsInstanceOf;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Test\Assertion\Stub\StubChild;
use Vivarium\Test\Assertion\Stub\StubContractA;
use Vivarium\Test\Assertion\Stub\StubParent;

use function preg_quote;

#[CoversClass(IsInstanceOf::class)]
final class IsInstanceOfTest extends TestCase
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

    #[TestWith([new ArrayObject(), 'Custom object ArrayObject.'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new IsInstanceOf(StubParent::class))->assert($value, 'Custom {value}.');
    }

    /** @param class-string $argument */
    #[TestWith(['Nope', 'Expected value to be the name of an existing class or interface. Got string "Nope".'])]
    public function testConstructorRejectsUnknownClass(string $argument, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        new IsInstanceOf($argument);
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $assertion = new IsInstanceOf(StubParent::class);

        return [
            'subclass instance' => [$assertion, new StubChild(), null],
            'interface' => [new IsInstanceOf(StubContractA::class), new StubChild(), null],
            'other class' => [
                $assertion,
                new ArrayObject(),
                'Expected value to be an instance of "' . StubParent::class . '". Got object ArrayObject.',
            ],
            'class name' => [
                $assertion,
                StubChild::class,
                'Expected value to be object. Got string "' . StubChild::class . '".',
            ],
        ];
    }
}
