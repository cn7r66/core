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

use function str_starts_with;

/** @template-extends BaseAssertion<mixed, string> */
final class StartsWith extends BaseAssertion
{
    public function __construct(private readonly string $prefix)
    {
    }

    #[Override]
    protected function precondition(): Assertion
    {
        return new IsString();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return str_starts_with($value, $this->prefix);
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure(
            'Expected value to start with {prefix}. Got {value}.',
            $value,
            prefix: $this->prefix,
        );
    }
}
