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
 * The default is re-evaluated on every call, as PHP does, so object defaults
 * such as `new Foo()` are not shared across resolutions.
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
