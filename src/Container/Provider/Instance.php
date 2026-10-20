<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Provider;

use Vivarium\Collection\Set\HashSet;
use Vivarium\Collection\Set\Set;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Provider;
use Vivarium\Type\Type;

use function is_callable;
use function is_object;

final class Instance implements Provider
{
    public function __construct(private mixed $instance)
    {
    }

    public function provide(Container $container): mixed
    {
        return $this->instance;
    }

    public function getTarget(): string
    {
        return Type::of($this->instance);
    }

    public function getCapabilities(): Set
    {
        if (is_object($this->instance)) {
            return HashSet::fromArray([
                Capability::INTERCEPTABLE,
                Capability::DECORABLE,
            ]);
        }

        if (is_callable($this->instance)) {
            return HashSet::fromArray([Capability::DECORABLE]);
        }

        return new HashSet();
    }
}
