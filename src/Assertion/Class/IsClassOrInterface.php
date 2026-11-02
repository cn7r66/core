<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Class;

use Override;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Assertion\Var\IsString;
use Vivarium\Failure\Failure;

use function class_exists;
use function interface_exists;

/** @template-extends BaseAssertion<class-string, string> */
final class IsClassOrInterface extends BaseAssertion
{
    #[Override]
    protected function precondition(): Assertion
    {
        return new IsString();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return class_exists($value) || interface_exists($value);
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure('Expected value to be the name of an existing class or interface. Got {value}.', $value);
    }
}
