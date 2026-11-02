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
use ReflectionObject;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Assertion\String\IsNotEmpty;
use Vivarium\Failure\Failure;

use function is_object;
use function property_exists;

/** @template-extends BaseAssertion<mixed, object|class-string> */
final class HasProtectedProperty extends BaseAssertion
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
        return property_exists($value, $this->property)
            && $this->reflect($value)->getProperty($this->property)->isProtected();
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure(
            'Expected value to have a protected property {property}. Got {value}.',
            $value,
            property: $this->property,
        );
    }

    /**
     * @param object|class-string $value
     *
     * @return ReflectionClass<object>
     */
    private function reflect(object|string $value): ReflectionClass
    {
        // ReflectionObject also sees dynamic properties, as property_exists() does.
        return is_object($value) ? new ReflectionObject($value) : new ReflectionClass($value);
    }
}
