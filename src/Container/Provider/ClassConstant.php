<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Provider;

use ReflectionClass;
use Vivarium\Assertion\Object\HasConstant;
use Vivarium\Assertion\Type\IsClassOrInterface;
use Vivarium\Collection\Set\HashSet;
use Vivarium\Collection\Set\Set;
use Vivarium\Container\Container;
use Vivarium\Container\Provider;
use Vivarium\Type\Type;

final class ClassConstant implements Provider
{
    public function __construct(
        private string $class,
        private string $name,
    ) {
        (new IsClassOrInterface())
            ->assert($class);

        (new HasConstant($name))
            ->assert($class);
    }

    public function provide(Container $container): mixed
    {
        return (new ReflectionClass($this->class))
            ->getReflectionConstant($this->name)
            ->getValue();
    }

    public function getTarget(): string
    {
        return Type::ofClassConstant($this->class, $this->name);
    }

    public function getCapabilities(): Set
    {
        return new HashSet();
    }
}
