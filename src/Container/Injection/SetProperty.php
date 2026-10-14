<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Container\Injection;

use ReflectionClass;
use Vivarium\Assertion\Object\HasProperty;
use Vivarium\Assertion\Type\IsAssignableTo;
use Vivarium\Assertion\Var\IsObject;
use Vivarium\Check\CheckIfObject;
use Vivarium\Container\Binding;
use Vivarium\Container\Container;
use Vivarium\Container\Injection;
use Vivarium\Container\Provider;

final class SetProperty implements Injection
{
    public function __construct(private string $property, private Binding $dependency)
    {
    }

    public function getSlot(): string
    {
        return $this->property;
    }

    public function enhance(mixed $instance, Container $container): mixed
    {
        (new IsObject())
            ->assert($instance);

        (new HasProperty($this->property))
            ->assert($instance);

        $property = (new ReflectionClass($instance))
            ->getProperty($this->property);

        $type = $property->hasType() ?
            (string) $property->getType() :
            'mixed';

        (new IsAssignableTo($type))
            ->assert($this->dependency->getType());

        $property->setValue(
            $instance,
            $container->get($this->dependency),
        );

        return $instance;
    }

    public function accept(Provider $provider): bool
    {
        return CheckIfObject::hasProperty(
            $provider->getTarget(),
            $this->property,
        );
    }
}
