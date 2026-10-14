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
 * A required, non-variadic parameter with no default value — the plain case
 * of a signature position.
 *
 * The default provider is built at construction, from the context in which
 * the parameter was declared; that context is not retained.
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

    /** Returns the parameter's name. */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the parameter's declared type identity.
     *
     * The type is already normalized and vetted by the caller that built
     * this parameter; this contract has no opinion on type syntax.
     */
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

    /** True if and only if an explicit argument has been bound via withArgument(). */
    public function isExplicit(): bool
    {
        return $this->explicit !== null;
    }

    /**
     * True if and only if $base is itself a required position and this
     * position's type is the same as or wider than the base's.
     *
     * An optional or variadic base position cannot be narrowed to required
     * without breaking PHP's own parameter-override rules; a required
     * position can only ever replace another required position. The type
     * check follows PHP's contravariance rule: replacing may widen the
     * accepted type, never narrow it.
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

    /**
     * A required position can never be omitted from the target signature it
     * is projected onto.
     */
    public function canBeOmitted(): bool
    {
        return false;
    }
}
