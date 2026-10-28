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

use function is_subclass_of;

/**
 * @template C of object
 * @template-extends BaseAssertion<class-string<C>, class-string>
 */
final class IsSubclassOf extends BaseAssertion
{
    /** @param class-string<C> $class */
    public function __construct(private readonly string $class)
    {
        (new IsClassOrInterface())
            ->assert($class);
    }

    #[Override]
    protected function precondition(): Assertion
    {
        return new IsClassOrInterface();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return is_subclass_of($value, $this->class, true);
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure(
            'Expected value to be a subclass of {class}. Got {value}.',
            $value,
            class: $this->class,
        );
    }
}
