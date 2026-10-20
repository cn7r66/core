<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Binding;

use Vivarium\Assertion\Object\HasMethod;
use Vivarium\Assertion\Object\HasProperty;
use Vivarium\Assertion\Type\IsAssignableToProperty;
use Vivarium\Assertion\Type\IsClass;
use Vivarium\Assertion\Type\IsClassOrInterface;
use Vivarium\Container\Binding;
use Vivarium\Container\Injection;
use Vivarium\Container\Injection\ImmutableMethodCall;
use Vivarium\Container\Injection\MethodCall;
use Vivarium\Container\Injection\SetProperty;
use Vivarium\Type\Type;

/** @template T */
final class InjectionBinder
{
    /** @var callable(Injection, int): T */
    private $create;

    public function __construct(
        private Binding $binding,
        callable $create,
        private int $defaultPriority,
    ) {
        $this->create = $create;
    }

    /** @return PriorityBinder<T> */
    public function onProperty(
        string $property,
        string $type = Binding::DEFAULT,
        string $tag = Binding::DEFAULT,
    ): PriorityBinder {
        (new IsClass())
            ->assert($this->binding->getType());

        (new HasProperty($property))
            ->assert($this->binding->getType());

        if ($type === Binding::DEFAULT) {
            $type = Type::ofProperty($this->binding->getType(), $property);
        }

        (new IsAssignableToProperty($this->binding->getType(), $property))
            ->assert($type);

        return $this->withInjection(
            new SetProperty(
                $property,
                new Binding($type, $tag, $this->binding->getType()),
            ),
        );
    }

    /** @return PriorityBinder<T> */
    public function onMethod(string $method): PriorityBinder
    {
        (new IsClassOrInterface())
            ->assert($this->binding->getType());

        (new HasMethod($method))
            ->assert($this->binding->getType());

        return $this->withInjection(
            new MethodCall($this->binding->getType(), $method),
        );
    }

    /** @return PriorityBinder<T> */
    public function onImmutableMethod(string $method): PriorityBinder
    {
        (new IsClassOrInterface())
            ->assert($this->binding->getType());

        (new HasMethod($method))
            ->assert($this->binding->getType());

        return $this->withInjection(
            new ImmutableMethodCall($this->binding->getType(), $method),
        );
    }

    /** @return PriorityBinder<T> */
    public function withInjection(Injection $injection): PriorityBinder
    {
        $create = $this->create;

        return new PriorityBinder(
            static fn (int $priority) => $create($injection, $priority),
            $this->defaultPriority,
        );
    }
}
