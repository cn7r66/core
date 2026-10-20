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
use Vivarium\Container\Binding;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Provider;
use Vivarium\Type\Type;

final class Fallback implements Provider
{
    public function __construct(
        private Binding $primary,
        private Provider $secondary,
    ) {
    }

    public function provide(Container $container): mixed
    {
        return $container->has($this->primary) ?
            $container->get($this->primary) : $this->secondary->provide($container);
    }

    public function getTarget(): string
    {
        return Type::union(
            $this->primary->getType(),
            $this->secondary->getTarget(),
        );
    }

    public function getCapabilities(): Set
    {
        return HashSet::fromArray([
            Capability::INTERCEPTABLE,
            Capability::DECORABLE,
        ]);
    }
}
