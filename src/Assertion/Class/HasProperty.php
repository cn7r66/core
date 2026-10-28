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

use function property_exists;

/** @template-extends BaseAssertion<mixed, object|class-string> */
final class HasProperty extends BaseAssertion
{
    public function __construct(private readonly string $property)
    {
        (new IsNotEmpty())
            ->assert($property);
    }

    #[Override]
    protected function precondition(): Assertion
    {
        return new IsObjectOrClass();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return property_exists($value, $this->property);
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure(
            'Expected value to have a property {property}. Got {value}.',
            $value,
            property: $this->property,
        );
    }
}
