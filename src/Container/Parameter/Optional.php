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
 * An optional, non-variadic parameter with a written default value.
 *
 * The container's binding for the type (in the declaring context) is tried
 * first and the written default is the fallback. An explicit argument
 * overrides both. A written `null` default is a real value.
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

    public function getName(): string
    {
        return $this->name;
    }

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

    public function isExplicit(): bool
    {
        return $this->explicit !== null;
    }

    /**
     * Whether this parameter can replace $base in an override.
     *
     * $base must be required or optional (a variadic base is rejected), and
     * this type must be the same as or wider than the base's.
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

    public function canBeOmitted(): bool
    {
        return true;
    }
}
