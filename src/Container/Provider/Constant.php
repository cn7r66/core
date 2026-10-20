<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Provider;

use Vivarium\Assertion\Var\IsConstant;
use Vivarium\Collection\Set\HashSet;
use Vivarium\Collection\Set\Set;
use Vivarium\Container\Container;
use Vivarium\Container\Provider;
use Vivarium\Type\Type;

use function constant;

final class Constant implements Provider
{
    public function __construct(private string $name)
    {
        (new IsConstant())
            ->assert($name);
    }

    public function provide(Container $container): mixed
    {
        return constant($this->name);
    }

    public function getTarget(): string
    {
        return Type::ofConstant($this->name);
    }

    public function getCapabilities(): Set
    {
        return new HashSet();
    }
}
