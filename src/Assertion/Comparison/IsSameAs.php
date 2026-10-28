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

use function is_object;

/**
 * @template T
 * @template-extends BaseAssertion<T, mixed>
 */
final class IsSameAs extends BaseAssertion
{
    /** @param T $compare */
    public function __construct(private readonly mixed $compare)
    {
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return $value === $this->compare;
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        // Two objects of one class render alike, so the plain template would read as a match.
        if (is_object($value) && is_object($this->compare) && $value::class === $this->compare::class) {
            return new Failure(
                'Expected value to be the same instance as {compare}. Got another instance.',
                $value,
                compare: $this->compare,
            );
        }

        return new Failure('Expected value to be the same as {compare}. Got {value}.', $value, compare: $this->compare);
    }
}
