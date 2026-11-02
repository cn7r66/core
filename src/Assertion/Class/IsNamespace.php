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

use function preg_match;

/** @template-extends BaseAssertion<non-empty-string, string> */
final class IsNamespace extends BaseAssertion
{
    private const string LABEL = '[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*';

    private const string NAME = '/^' . self::LABEL . '(\\\\' . self::LABEL . ')*$/D';

    #[Override]
    protected function precondition(): Assertion
    {
        return new IsString();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return preg_match(self::NAME, $value) === 1;
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure('Expected value to be a valid namespace name. Got {value}.', $value);
    }
}
