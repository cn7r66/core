<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Type;

use Override;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Failure\Failure;
use Vivarium\Type\Exception\InvalidType;
use Vivarium\Type\Type;
use Vivarium\Type\Types;

use function is_string;

/** @template-extends BaseAssertion<mixed, mixed> */
final class IsType extends BaseAssertion
{
    #[Override]
    protected function condition(mixed $value): bool
    {
        if ($value instanceof Type) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        try {
            Types::fromString($value);
        } catch (InvalidType) {
            return false;
        }

        return true;
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure('Expected value to be a type. Got {value}.', $value);
    }
}
