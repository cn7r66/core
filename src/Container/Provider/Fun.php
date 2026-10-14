<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Provider;

use Closure;
use Vivarium\Assertion\Var\IsFunction;
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
 * Invokes a Closure or a named function.
 *
 * Parameters are resolved like any callable's: autowired from the container
 * by declared type, explicitly bindable through the Caller surface, optional
 * parameters container-first with written-default fallback, untyped
 * parameters under `mixed`. There is no implicit container argument.
 */
final class Fun implements Provider, Caller, Equality
{
    private Signature $signature;

    public function __construct(private Closure|string $fn)
    {
        (new IsFunction())
            ->assert($fn);

        $this->signature = Signature::ofFunction($fn);
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
                $fun            = clone $this;
                $fun->signature = $this->signature->withArgument($position, $provider);

                return $fun;
            },
        );
    }

    public function provide(Container $container): mixed
    {
        return ($this->fn)(...$this->resolveArguments($container));
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
        return Type::ofFunction($this->fn);
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
            ->append($this->fn, $other->fn)
            ->isEquals();
    }

    public function hash(): string
    {
        return (new HashBuilder())
            ->append($this->fn)
            ->getHashCode();
    }
}
