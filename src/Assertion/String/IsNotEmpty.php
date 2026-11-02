<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\String;

use Override;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Assertion\Var\IsString;
use Vivarium\Failure\Failure;

/** @template-extends BaseAssertion<non-empty-string, string> */
final class IsNotEmpty extends BaseAssertion
{
    #[Override]
    protected function precondition(): Assertion
    {
        return new IsString();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return $value !== '';
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure('Expected value not to be an empty string. Got {value}.', $value);
    }
}
