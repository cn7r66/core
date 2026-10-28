<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Class;

use Override;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Assertion\Failure;

use function class_exists;
use function interface_exists;
use function is_object;
use function is_string;

/** @template-extends BaseAssertion<mixed, mixed> */
final class IsObjectOrClass extends BaseAssertion
{
    #[Override]
    protected function condition(mixed $value): bool
    {
        return is_object($value)
            || (is_string($value) && (class_exists($value) || interface_exists($value)));
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure('Expected value to be an object or the name of a class or interface. Got {value}.', $value);
    }
}
