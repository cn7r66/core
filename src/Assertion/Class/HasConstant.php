<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Class;

use Override;
use ReflectionClass;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Assertion\Failure;
use Vivarium\Assertion\String\IsNotEmpty;

/** @template-extends BaseAssertion<mixed, object|class-string> */
final class HasConstant extends BaseAssertion
{
    public function __construct(private readonly string $constant)
    {
        (new IsNotEmpty())
            ->assert($constant);
    }

    #[Override]
    protected function precondition(): Assertion
    {
        return new IsObjectOrClass();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return (new ReflectionClass($value))->hasConstant($this->constant);
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure(
            'Expected value to have a constant {constant}. Got {value}.',
            $value,
            constant: $this->constant,
        );
    }
}
