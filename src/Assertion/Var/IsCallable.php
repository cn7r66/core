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

use function is_callable;

/** @template-extends BaseAssertion<callable, mixed> */
final class IsCallable extends BaseAssertion
{
    #[Override]
    protected function condition(mixed $value): bool
    {
        return is_callable($value);
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure('Expected value to be callable. Got {value}.', $value);
    }
}
