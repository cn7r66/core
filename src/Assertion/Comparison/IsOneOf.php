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
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Failure;
use Vivarium\Equality\Equal;

/** @template-extends BaseAssertion<mixed, mixed> */
final class IsOneOf extends BaseAssertion
{
    /** @param array<mixed> $choices */
    public function __construct(private readonly array $choices)
    {
        $this->assertNotEmpty($choices);
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

    /** @param array<mixed> $choices */
    private function assertNotEmpty(array $choices): void
    {
        if ($choices === []) {
            throw new AssertionFailed(new Failure('Expected choices not to be empty.', $choices));
        }
    }
}
