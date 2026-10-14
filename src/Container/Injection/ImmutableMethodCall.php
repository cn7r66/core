<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Injection;

use ReflectionClass;
use Vivarium\Assertion\Object\HasPublicMethod;
use Vivarium\Assertion\Type\IsAssignableTo;
use Vivarium\Container\Binding;
use Vivarium\Container\Binding\ProviderBinder;
use Vivarium\Container\Caller;
use Vivarium\Container\Container;
use Vivarium\Container\Injection;
use Vivarium\Container\Parameter;
use Vivarium\Container\Provider;
use Vivarium\Container\Signature;
use Vivarium\Equality\Equality;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;
use Vivarium\Type\Type;

use function array_pop;
use function array_values;
use function class_exists;

/**
 * Invokes an immutable method on the produced instance and adopts its
 * return value as the replacement instance.
 */
final class ImmutableMethodCall implements Injection, Caller, Equality
{
    private Signature $signature;

    public function __construct(private string $class, private string $method)
    {
        $this->signature = Signature::ofMethod($class, $method);

        (new HasPublicMethod($this->method))
            ->assert($this->class);
    }

    public function getSlot(): string
    {
        return $this->method;
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
                $call            = clone $this;
                $call->signature = $this->signature->withArgument($position, $provider);

                return $call;
            },
        );
    }

    public function enhance(mixed $instance, Container $container): mixed
    {
        (new IsAssignableTo($this->class))
            ->assert($instance::class);

        // The configured signature is projected onto the runtime one so a subclass's
        // narrower override is honoured; projection violations propagate as-is.
        $signature = $this->signature->project(
            Signature::ofMethod($instance::class, $this->method),
        );

        $result = (new ReflectionClass($instance::class))
            ->getMethod($this->method)
            ->invokeArgs($instance, $this->resolveArguments($signature, $container));

        // An immutable call returns a replacement, which must remain assignable to
        // the instance's class.
        (new IsAssignableTo($instance::class))
            ->assert($result::class);

        return $result;
    }

    /** @return array<mixed> */
    private function resolveArguments(Signature $signature, Container $container): array
    {
        $providers = $signature->getArguments();

        $values = [];
        for ($position = 0; $position < $signature->count(); $position++) {
            $values[] = $providers[$position]->provide($container);
        }

        if ($signature->count() === 0) {
            return $values;
        }

        $lastParameter = $signature->getParameter($signature->count() - 1);

        if (! $lastParameter instanceof Parameter\Variadic) {
            return $values;
        }

        $variadicValues = array_pop($values);

        foreach (array_values($variadicValues) as $variadicValue) {
            $values[] = $variadicValue;
        }

        return $values;
    }

    public function accept(Provider $provider): bool
    {
        $target = $provider->getTarget();

        return class_exists($target)
            && (new ReflectionClass($target))->hasMethod($this->method);
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
