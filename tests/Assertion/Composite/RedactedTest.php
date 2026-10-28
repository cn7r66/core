<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Composite;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\Comparison\IsSameAs;
use Vivarium\Assertion\Composite\Redacted;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Var\IsConstant;

use function preg_quote;

#[CoversClass(Redacted::class)]
final class RedactedTest extends TestCase
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

    public function testAssertWithCustomMessage(): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote('Custom [redacted].', '/') . '$/');

        (new Redacted(new IsSameAs('secret')))->assert('guess', 'Custom {value}.');
    }

    public function testAssertMasksEveryParameter(): void
    {
        try {
            (new Redacted(new IsSameAs('secret')))->assert('guess');
        } catch (AssertionFailed $failed) {
            self::assertSame(
                'Expected value to be the same as {compare}. Got {value}.',
                $failed->getFailure()->getTemplate(),
            );
            self::assertSame(
                ['value' => '[redacted]', 'compare' => '[redacted]'],
                $failed->getFailure()->getParameters(),
            );
            self::assertNull($failed->getPrevious());

            return;
        }

        self::fail('Expected AssertionFailed.');
    }

    public function testAssertPassesMessage(): void
    {
        $child = $this->createMock(Assertion::class);
        $child->expects(self::once())->method('assert')->with('a', 'Custom');

        (new Redacted($child))->assert('a', 'Custom');
    }

    public function testCheckNeverAsserts(): void
    {
        $child = $this->createMock(Assertion::class);
        $child->expects(self::never())->method('assert');
        $child->method('check')->willReturn(true);

        (new Redacted($child))->check(1);
    }

    /** @return array<string, array{Assertion<mixed>, mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'same' => [new Redacted(new IsSameAs('secret')), 'secret', null],
            'other' => [
                new Redacted(new IsSameAs('secret')),
                'guess',
                'Expected value to be the same as [redacted]. Got [redacted].',
            ],
            'precondition' => [new Redacted(new IsConstant()), 42, 'Expected value to be string. Got [redacted].'],
        ];
    }
}
