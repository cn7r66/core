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
use Vivarium\Assertion\Class\HasPrivateProperty;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Test\Assertion\Stub\StubChild;
use Vivarium\Test\Assertion\Stub\StubParent;

use function preg_quote;

#[CoversClass(HasPrivateProperty::class)]
final class HasPrivatePropertyTest extends TestCase
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

    #[TestWith([StubChild::class, 'Custom string "' . StubChild::class . '".'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        (new HasPrivateProperty('x'))->assert($value, 'Custom {value}.');
    }

    #[TestWith(['', 'Expected value not to be an empty string. Got string "".'])]
    public function testConstructorRejectsEmptyName(string $argument, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        new HasPrivateProperty($argument);
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'own private' => [new HasPrivateProperty('hidden'), StubParent::class, null],
            'parent private, seen from the child' => [
                new HasPrivateProperty('hidden'),
                StubChild::class,
                'Expected value to have a private property "hidden". Got string "' . StubChild::class . '".',
            ],
            'public' => [
                new HasPrivateProperty('visible'),
                StubChild::class,
                'Expected value to have a private property "visible". Got string "' . StubChild::class . '".',
            ],
            'public private(set)' => [
                new HasPrivateProperty('locked'),
                StubChild::class,
                'Expected value to have a private property "locked". Got string "' . StubChild::class . '".',
            ],
            'not a class' => [
                new HasPrivateProperty('x'),
                1,
                'Expected value to be an object or the name of a class or interface. Got int 1.',
            ],
        ];
    }
}
