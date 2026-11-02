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
use Vivarium\Type\Type;
use Vivarium\Type\Types;

/** @template-extends BaseAssertion<mixed, string|Type> */
final class IsAssignableTo extends BaseAssertion
{
    private readonly Type $target;

    public function __construct(string|Type $target)
    {
        (new IsType())
            ->assert($target);

        $this->target = Types::from($target);
    }

    #[Override]
    protected function precondition(): Assertion
    {
        return new IsType();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return $this->target->accepts(Types::from($value));
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure(
            'Expected value to be assignable to {target}. Got {type}.',
            $value,
            target: (string) $this->target,
            type: (string) Types::from($value),
        );
    }
}
