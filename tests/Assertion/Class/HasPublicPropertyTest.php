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
use stdClass;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\Class\HasPublicProperty;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Test\Assertion\Stub\StubChild;

use function preg_quote;

#[CoversClass(HasPublicProperty::class)]
final class HasPublicPropertyTest extends TestCase
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

        (new HasPublicProperty('x'))->assert($value, 'Custom {value}.');
    }

    #[TestWith(['', 'Expected value not to be an empty string. Got string "".'])]
    public function testConstructorRejectsEmptyName(string $argument, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        new HasPublicProperty($argument);
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        $dynamic      = new stdClass();
        $dynamic->dyn = 1;

        return [
            'public' => [new HasPublicProperty('visible'), StubChild::class, null],
            'public private(set)' => [new HasPublicProperty('locked'), StubChild::class, null],
            'dynamic' => [new HasPublicProperty('dyn'), $dynamic, null],
            'protected' => [
                new HasPublicProperty('shielded'),
                StubChild::class,
                'Expected value to have a public property "shielded". Got string "' . StubChild::class . '".',
            ],
            'not a class' => [
                new HasPublicProperty('x'),
                1,
                'Expected value to be an object or the name of a class or interface. Got int 1.',
            ],
        ];
    }
}
