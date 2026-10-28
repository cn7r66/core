<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Comparison;

use Override;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Assertion\Failure;
use Vivarium\Assertion\Var\IsBoolean;

/** @template-extends BaseAssertion<true, bool> */
final class IsTrue extends BaseAssertion
{
    #[Override]
    protected function precondition(): Assertion
    {
        return new IsBoolean();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return $value === true;
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure('Expected value to be true. Got {value}.', $value);
    }
}
