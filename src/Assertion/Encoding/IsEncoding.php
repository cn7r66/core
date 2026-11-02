<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Encoding;

use Override;
use ValueError;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Assertion\Var\IsString;
use Vivarium\Failure\Failure;

use function mb_check_encoding;

/** @template-extends BaseAssertion<non-empty-string, string> */
final class IsEncoding extends BaseAssertion
{
    #[Override]
    protected function precondition(): Assertion
    {
        return new IsString();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        try {
            // mb_check_encoding() throws for an encoding mbstring doesn't know; '' is valid in every known one.
            return mb_check_encoding('', $value);
        } catch (ValueError) {
            return false;
        }
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure('Expected value to be an encoding known to mbstring. Got {value}.', $value);
    }
}
