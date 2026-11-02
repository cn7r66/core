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
use Vivarium\Assertion\Composite\Not;
use Vivarium\Equality\Equal;
use Vivarium\Failure\Failure;

/** @template-extends BaseAssertion<mixed, mixed> */
final class IsOneOf extends BaseAssertion
{
    /** @param array<mixed> $choices */
    public function __construct(private readonly array $choices)
    {
        (new Not(
            new IsSameAs([]),
        ))->assert($choices, 'Expected choices not to be empty.');
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        foreach ($this->choices as $choice) {
            if (Equal::areEquals($choice, $value)) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure('Expected value to be one of {choices}. Got {value}.', $value, choices: $this->choices);
    }
}
