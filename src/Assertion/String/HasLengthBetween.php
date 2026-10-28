<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\String;

use Override;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Assertion\Encoding\IsEncoding;
use Vivarium\Assertion\Failure;
use Vivarium\Assertion\Numeric\IsGreaterThanOrEqualTo;
use Vivarium\Assertion\Numeric\IsLessThanOrEqualTo;
use Vivarium\Assertion\Var\IsString;

use function mb_strlen;

/** @template-extends BaseAssertion<mixed, string> */
final class HasLengthBetween extends BaseAssertion
{
    public function __construct(
        private readonly int $min,
        private readonly int $max,
        private readonly string $encoding = 'UTF-8',
    ) {
        (new IsGreaterThanOrEqualTo(0))
            ->assert($min, 'Expected minimum length to be greater than or equal to 0. Got {value}.');

        (new IsLessThanOrEqualTo($max))
            ->assert($min, 'Expected minimum length not to exceed maximum length {compare}. Got {value}.');

        (new IsEncoding())
            ->assert($encoding);
    }

    #[Override]
    protected function precondition(): Assertion
    {
        return new IsString();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return $this->min <= mb_strlen($value, $this->encoding)
            && mb_strlen($value, $this->encoding) <= $this->max;
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure(
            'Expected value to have a length between {min} and {max}. Got {value}.',
            $value,
            min: $this->min,
            max: $this->max,
        );
    }
}
