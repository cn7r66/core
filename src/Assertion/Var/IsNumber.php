<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Var;

use Override;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Assertion\Failure;

use function is_float;
use function is_int;

/** @template-extends BaseAssertion<mixed, mixed> */
final class IsNumber extends BaseAssertion
{
    #[Override]
    protected function condition(mixed $value): bool
    {
        return is_int($value) || is_float($value);
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure('Expected value to be int or float. Got {value}.', $value);
    }
}
