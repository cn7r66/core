<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Provider;

use ReflectionParameter;
use Vivarium\Assertion\Boolean\IsTrue;
use Vivarium\Collection\Set\HashSet;
use Vivarium\Collection\Set\Set;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Provider;
use Vivarium\Type\Type;

/**
 * Provides the declared default value of an optional parameter.
 *
 * `ReflectionParameter::getDefaultValue()` re-evaluates the initializer
 * expression on every call, so this provider re-evaluates it inside
 * `provide()` rather than caching the result: for object-creating defaults
 * (PHP 8.1+ `new XXX(...)` initializers) freezing one evaluation would
 * share a single instance across every resolution, whereas PHP's own
 * semantics give each call site that omits the argument a fresh instance.
 * The default is never evaluated at construction: the target is the
 * parameter's declared type, and evaluating an object-creating initializer
 * eagerly would run user code just to inspect its result.
 */
final class DefaultValue implements Provider
{
    private string $target;

    public function __construct(private ReflectionParameter $parameter)
    {
        (new IsTrue())
            ->assert($parameter->isDefaultValueAvailable());

        $this->target = Type::fromReflectionType($parameter->getType());
    }

    public function provide(Container $container): mixed
    {
        return $this->parameter->getDefaultValue();
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getCapabilities(): Set
    {
        return HashSet::fromArray([
            Capability::INTERCEPTABLE,
            Capability::DECORABLE,
        ]);
    }
}
