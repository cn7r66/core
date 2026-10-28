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
use Vivarium\Assertion\Failure;
use Vivarium\Assertion\Var\IsString;

use function mb_regex_encoding;

/** @template-extends BaseAssertion<non-empty-string, string> */
final class IsRegexEncoding extends BaseAssertion
{
    #[Override]
    protected function precondition(): Assertion
    {
        return new IsString();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        // PHP offers no lookup: the only way to ask is to set the global encoding and restore it.
        $current = mb_regex_encoding();

        try {
            return mb_regex_encoding($value);
        } catch (ValueError) {
            return false;
        } finally {
            mb_regex_encoding($current);
        }
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure(
            'Expected value to be an encoding supported by mbstring regex functions. Got {value}.',
            $value,
        );
    }
}
