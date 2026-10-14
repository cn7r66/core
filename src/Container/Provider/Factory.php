<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Provider;

use ReflectionClass;
use Vivarium\Assertion\Object\HasPublicMethod;
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

/**
 * Resolves a factory instance from the container by its binding, then
 * invokes the configured method on that instance to produce the value.
 */
final class Factory implements Provider, Caller, Equality
{
    private Signature $signature;

    public function __construct(private Binding $factory, private string $method)
    {
        $this->signature = Signature::ofMethod($factory->getType(), $method);

        (new HasPublicMethod($this->method))
            ->assert($this->factory->getType());
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
                $factory            = clone $this;
                $factory->signature = $this->signature->withArgument($position, $provider);

                return $factory;
            },
        );
    }

    public function provide(Container $container): mixed
    {
        $instance = $container->get($this->factory);

        return (new ReflectionClass($this->factory->getType()))
            ->getMethod($this->method)
            ->invokeArgs($instance, $this->resolveArguments($container));
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
        return Type::ofMethod($this->factory->getType(), $this->method);
    }

    public function getCapabilities(): Set
    {
        return HashSet::fromArray([
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
            ->append($this->factory, $other->factory)
            ->append($this->method, $other->method)
            ->isEquals();
    }

    public function hash(): string
    {
        return (new HashBuilder())
            ->append($this->factory)
            ->append($this->method)
            ->getHashCode();
    }
}
