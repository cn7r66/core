<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Parameter;

use Vivarium\Assertion\String\IsNotEmpty;
use Vivarium\Assertion\Type\IsAssignableTo;
use Vivarium\Container\Binding;
use Vivarium\Container\Parameter;
use Vivarium\Container\Provider;
use Vivarium\Container\Provider\ContainerCall;
use Vivarium\Type\Type;

/**
 * A required, non-variadic parameter with no default value.
 *
 * The default provider is built at construction from the declaring context,
 * which is not retained.
 */
final class Required implements Parameter
{
    private Provider $default;

    private Provider|null $explicit = null;

    public function __construct(private string $name, private string $type, string $context)
    {
        (new IsNotEmpty())
            ->assert($name);

        (new IsNotEmpty())
            ->assert($type);

        $this->default = new ContainerCall(
            new Binding(
                $this->type,
                Binding::DEFAULT,
                $context,
            ),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns the explicitly bound provider if one was set with
     * withArgument(), otherwise the default provider built at construction.
     */
    public function getArgument(): Provider
    {
        if ($this->explicit !== null) {
            return $this->explicit;
        }

        return $this->default;
    }

    /**
     * Returns a new Required with $provider bound as its explicit argument.
     */
    public function withArgument(Provider $provider): Parameter
    {
        (new IsAssignableTo($this->type))
            ->assert($provider->getTarget());

        $parameter           = clone $this;
        $parameter->explicit = $provider;

        return $parameter;
    }

    public function isExplicit(): bool
    {
        return $this->explicit !== null;
    }

    /**
     * Whether this parameter can replace $base in an override.
     *
     * $base must be required (an optional or variadic base cannot become
     * required), and this type must be the same as or wider than the base's.
     */
    public function canReplace(Parameter $base): bool
    {
        if (! $base instanceof Required) {
            return false;
        }

        if ($this->type === Type::MIXED) {
            return true;
        }

        return (new IsAssignableTo($this->type))($base->getType());
    }

    public function canBeOmitted(): bool
    {
        return false;
    }
}
