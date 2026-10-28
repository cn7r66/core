<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Var;

use Closure;
use Override;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Assertion\Failure;

use function function_exists;
use function is_string;

/** @template-extends BaseAssertion<mixed, mixed> */
final class IsFunction extends BaseAssertion
{
    #[Override]
    protected function condition(mixed $value): bool
    {
        return $value instanceof Closure
            || (is_string($value)
            && function_exists($value));
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure('Expected value to be a Closure or a function name. Got {value}.', $value);
    }
}
