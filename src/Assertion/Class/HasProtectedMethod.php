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
use Vivarium\Assertion\String\IsNotEmpty;
use Vivarium\Failure\Failure;

use function is_object;
use function method_exists;

/** @template-extends BaseAssertion<mixed, object|class-string> */
final class HasProtectedMethod extends BaseAssertion
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
        $class = is_object($value) ? $value::class : $value;

        return method_exists($class, $this->method)
            && (new ReflectionClass($class))->getMethod($this->method)->isProtected();
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure(
            'Expected value to have a protected method {method}. Got {value}.',
            $value,
            method: $this->method,
        );
    }
}
