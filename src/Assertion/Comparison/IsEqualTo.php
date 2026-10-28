<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Comparison;

use Override;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Assertion\Failure;
use Vivarium\Equality\Equal;

/** @template-extends BaseAssertion<mixed, mixed> */
final class IsEqualTo extends BaseAssertion
{
    public function __construct(private readonly mixed $compare)
    {
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return Equal::areEquals($this->compare, $value);
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure('Expected value to be equal to {compare}. Got {value}.', $value, compare: $this->compare);
    }
}
