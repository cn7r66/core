<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Parameter;

use ReflectionParameter;
use Vivarium\Assertion\String\IsNotEmpty;
use Vivarium\Assertion\Type\IsAssignableTo;
use Vivarium\Container\Binding;
use Vivarium\Container\Parameter;
use Vivarium\Container\Provider;
use Vivarium\Container\Provider\DefaultValue;
use Vivarium\Container\Provider\Fallback;
use Vivarium\Type\Type;

/**
 * An optional, non-variadic parameter carrying its own written default value.
 *
 * The default is owned by this position, not merely absent metadata: a
 * written `null` default is a legitimate value, distinct from a required
 * position that has no default at all. The default is served as a
 * container-first fallback: the container's binding for this position's type
 * in its declaring context is consulted first, and the written default is
 * served only when the container has no such binding. An explicit argument
 * binding, once set via withArgument(), overrides both.
 */
final class Optional implements Parameter
{
    private Provider $default;

    private Provider|null $explicit = null;

    public function __construct(
        private string $name,
        private string $type,
        string $context,
        ReflectionParameter $parameter,
    ) {
        (new IsNotEmpty())
            ->assert($name);

        (new IsNotEmpty())
            ->assert($type);

        $this->default = new Fallback(
            new Binding($this->type, Binding::DEFAULT, $context),
            new DefaultValue($parameter),
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
     * Returns the explicitly bound argument if one was set via
     * withArgument(), otherwise this position's own default provider.
     */
    public function getArgument(): Provider
    {
        if ($this->explicit !== null) {
            return $this->explicit;
        }

        return $this->default;
    }

    /**
     * Returns a new Optional with $provider bound as its explicit argument,
     * replacing any previously bound argument.
     */
    public function withArgument(Provider $provider): Parameter
    {
        (new IsAssignableTo($this->type))
            ->assert($provider->getTarget());

        $parameter           = clone $this;
        $parameter->explicit = $provider;

        return $parameter;
    }

    /**
     * True if and only if an explicit argument has been bound via
     * withArgument().
     */
    public function isExplicit(): bool
    {
        return $this->explicit !== null;
    }

    /**
     * True if and only if $base is a required or optional position and this
     * position's type is the same as or wider than the base's.
     *
     * An optional position is more permissive than a required one, so it may
     * legally replace either kind. A variadic base cannot be narrowed to a
     * non-variadic position and is therefore rejected. The type check
     * follows PHP's contravariance rule: replacing may widen the accepted
     * type, never narrow it.
     */
    public function canReplace(Parameter $base): bool
    {
        if (! $base instanceof Required && ! $base instanceof Optional) {
            return false;
        }

        if ($this->type === Type::MIXED) {
            return true;
        }

        return (new IsAssignableTo($this->type))($base->getType());
    }

    /**
     * An optional position may always be omitted from the target signature
     * it is projected onto.
     */
    public function canBeOmitted(): bool
    {
        return true;
    }
}
