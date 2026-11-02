<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion;

use LogicException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Var\IsString;
use Vivarium\Failure\Failure;

use function preg_quote;

#[CoversClass(BaseAssertion::class)]
final class BaseAssertionTest extends TestCase
{
    #[DataProvider('provideValues')]
    public function testCheck(mixed $value, string|null $message): void
    {
        self::assertSame($message === null, $this->atMostThree()->check($value));
    }

    #[DataProvider('provideValues')]
    public function testAssert(mixed $value, string|null $message): void
    {
        if ($message === null) {
            $this->expectNotToPerformAssertions();
        }

        if ($message !== null) {
            $this->expectException(AssertionFailed::class);
            $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');
        }

        $this->atMostThree()->assert($value);
    }

    #[TestWith([5, 'Custom int 5 over 3.'])]
    public function testAssertWithCustomMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        $this->atMostThree()->assert($value, 'Custom {value} over {limit}.');
    }

    #[TestWith([1, 'Expected value to be string. Got int 1.'])]
    public function testAssertSkipsCondition(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        $this->unreachableCondition()->assert($value);
    }

    #[TestWith([1, 'Expected value to be string. Got int 1.'])]
    public function testAssertKeepsPreconditionMessage(mixed $value, string $message): void
    {
        $this->expectException(AssertionFailed::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');

        $this->unreachableCondition()->assert($value, 'Custom {value}.');
    }

    #[TestWith([1])]
    public function testCheckSkipsCondition(mixed $value): void
    {
        self::assertFalse($this->unreachableCondition()->check($value));
    }

    /** @return array<string, array{mixed, string|null}> */
    public static function provideValues(): array
    {
        return [
            'within the limit' => [3, null],
            'null through the default precondition' => [null, null],
            'over the limit' => [5, 'Expected at most 3. Got int 5.'],
        ];
    }

    /** @return Assertion<mixed> */
    private function atMostThree(): Assertion
    {
        return new /** @template-extends BaseAssertion<mixed, mixed> */ class extends BaseAssertion {
            private const int LIMIT = 3;

            #[Override]
            protected function condition(mixed $value): bool
            {
                return $value <= self::LIMIT;
            }

            #[Override]
            protected function failure(mixed $value): Failure
            {
                return new Failure('Expected at most {limit}. Got {value}.', $value, limit: self::LIMIT);
            }
        };
    }

    /** @return Assertion<string> */
    private function unreachableCondition(): Assertion
    {
        return new /** @template-extends BaseAssertion<string, string> */ class extends BaseAssertion {
            #[Override]
            protected function precondition(): Assertion
            {
                return new IsString();
            }

            #[Override]
            protected function condition(mixed $value): bool
            {
                throw new LogicException('The condition must not run when the precondition fails.');
            }

            #[Override]
            protected function failure(mixed $value): Failure
            {
                return new Failure('Unreachable {value}.', $value);
            }
        };
    }
}
