<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Provider;

use ReflectionClass;
use Vivarium\Assertion\Boolean\IsTrue;
use Vivarium\Collection\Set\HashSet;
use Vivarium\Collection\Set\Set;
use Vivarium\Container\Binding;
use Vivarium\Container\Binding\ProviderBinder;
use Vivarium\Container\Caller;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Parameter;
use Vivarium\Container\Provider;
use Vivarium\Container\Signature;
use Vivarium\Equality\Equality;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;
use Vivarium\Type\Type;

use function array_pop;
use function array_values;
use function sprintf;

/**
 * Builds an instance of a class through its constructor.
 *
 * When the class has no constructor, it instantiates with zero arguments.
 */
final class Constructor implements Provider, Caller, Equality
{
    private Signature $signature;

    public function __construct(private string $class)
    {
        $this->signature = Signature::ofConstructor($class);

        // Signature::ofConstructor accepts abstract classes, so instantiability is checked here.
        (new IsTrue())
            ->assert(
                (new ReflectionClass($class))->isInstantiable(),
                sprintf('Class %s is not instantiable.', $class),
            );
    }

    /** {@inheritDoc} */
    public function bindArgument(string $name): ProviderBinder
    {
        return $this->bindArgumentAtPosition(
            $this->signature->positionOf($name),
        );
    }

    /** {@inheritDoc} */
    public function bindArgumentAtPosition(int $position): ProviderBinder
    {
        $parameter = $this->signature->getParameter($position);

        $type = $parameter instanceof Parameter\Variadic ?
            Type::ARRAY : $parameter->getType();

        $binding = new Binding($type);

        return new ProviderBinder(
            $binding,
            function (Binding $source, Provider $provider) use ($position): self {
                $constructor            = clone $this;
                $constructor->signature = $this->signature->withArgument($position, $provider);

                return $constructor;
            },
        );
    }

    public function provide(Container $container): mixed
    {
        return (new ReflectionClass($this->class))
            ->newInstanceArgs(
                $this->resolveArguments($container),
            );
    }

    /** @return array<mixed> */
    private function resolveArguments(Container $container): array
    {
        $providers = $this->signature->getArguments();

        $values = [];
        for ($position = 0; $position < $this->signature->count(); $position++) {
            $values[] = $providers[$position]->provide($container);
        }

        if ($this->signature->count() === 0) {
            return $values;
        }

        $lastParameter = $this->signature->getParameter($this->signature->count() - 1);

        if (! $lastParameter instanceof Parameter\Variadic) {
            return $values;
        }

        $variadicValues = array_pop($values);

        foreach (array_values($variadicValues) as $variadicValue) {
            $values[] = $variadicValue;
        }

        return $values;
    }

    public function getTarget(): string
    {
        return $this->class;
    }

    public function getCapabilities(): Set
    {
        return HashSet::fromArray([
            Capability::INJECTABLE,
            Capability::INTERCEPTABLE,
            Capability::DECORABLE,
        ]);
    }

    public function equals(object $object): bool
    {
        if ($object === $this) {
            return true;
        }

        if ($object::class !== $this::class) {
            return false;
        }

        $other = $object;

        return (new EqualsBuilder())
            ->append($this->class, $other->class)
            ->isEquals();
    }

    public function hash(): string
    {
        return (new HashBuilder())
            ->append($this->class)
            ->getHashCode();
    }
}
