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
use Vivarium\Assertion\Failure;
use Vivarium\Assertion\String\IsNotEmpty;

use function method_exists;

/** @template-extends BaseAssertion<mixed, object|class-string> */
final class HasMethod extends BaseAssertion
{
    public function __construct(private readonly string $method)
    {
        (new IsNotEmpty())
            ->assert($method);
    }

    #[Override]
    protected function precondition(): Assertion
    {
        return new IsObjectOrClass();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return method_exists($value, $this->method);
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure(
            'Expected value to have a method {method}. Got {value}.',
            $value,
            method: $this->method,
        );
    }
}
