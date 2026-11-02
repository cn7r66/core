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
use Vivarium\Failure\Failure;

use function is_bool;

/** @template-extends BaseAssertion<bool, mixed> */
final class IsBoolean extends BaseAssertion
{
    #[Override]
    protected function condition(mixed $value): bool
    {
        return is_bool($value);
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure('Expected value to be bool. Got {value}.', $value);
    }
}
