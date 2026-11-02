<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Type;

use Override;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Failure\Failure;
use Vivarium\Type\Primitive;
use Vivarium\Type\Type;
use Vivarium\Type\Types;

/** @template-extends BaseAssertion<mixed, string|Type> */
final class IsPrimitive extends BaseAssertion
{
    #[Override]
    protected function precondition(): Assertion
    {
        return new IsType();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return Types::from($value) instanceof Primitive;
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure(
            'Expected value to be a primitive type. Got {type}.',
            $value,
            type: (string) Types::from($value),
        );
    }
}
