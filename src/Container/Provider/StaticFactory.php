<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Provider;

use ReflectionClass;
use ReflectionMethod;
use Vivarium\Assertion\Boolean\IsTrue;
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
use function sprintf;

/**
 * Invokes a static method on a class to produce the value.
 */
final class StaticFactory implements Provider, Caller, Equality
{
    private Signature $signature;

    public function __construct(private string $class, private string $method)
    {
        $this->signature = Signature::ofMethod($class, $method);

        (new HasPublicMethod($this->method))
            ->assert($this->class);

        (new IsTrue())
            ->assert(
                (new ReflectionMethod($class, $method))->isStatic(),
                sprintf('Method %s::%s is not static.', $class, $method),
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
                $factory            = clone $this;
                $factory->signature = $this->signature->withArgument($position, $provider);

                return $factory;
            },
        );
    }

    public function provide(Container $container): mixed
    {
        return (new ReflectionClass($this->class))
            ->getMethod($this->method)
            ->invokeArgs(null, $this->resolveArguments($container));
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
        return Type::ofMethod($this->class, $this->method);
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
            ->append($this->class, $other->class)
            ->append($this->method, $other->method)
            ->isEquals();
    }

    public function hash(): string
    {
        return (new HashBuilder())
            ->append($this->class)
            ->append($this->method)
            ->getHashCode();
    }
}
