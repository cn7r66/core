<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Numeric;

use Override;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Assertion\Var\IsNumber;
use Vivarium\Failure\Failure;

/** @template-extends BaseAssertion<mixed, int|float> */
final class IsGreaterThan extends BaseAssertion
{
    public function __construct(private readonly int|float $compare)
    {
    }

    #[Override]
    protected function precondition(): Assertion
    {
        return new IsNumber();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return $value > $this->compare;
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure(
            'Expected value to be greater than {compare}. Got {value}.',
            $value,
            compare: $this->compare,
        );
    }
}
