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
use Vivarium\Container\Provider\Fallback;
use Vivarium\Container\Provider\Instance;
use Vivarium\Type\Type;

/**
 * A variadic parameter (`...$args`).
 *
 * PHP forbids a default value on a variadic position, so this kind never
 * owns a written default; it still resolves to a default provider.
 */
final class Variadic implements Parameter
{
    private Provider|null $explicit = null;

    private Provider $default;

    /**
     * The default provider is the container's `array` binding in this context,
     * or an empty array when nothing is bound. All variadic parameters in a
     * context share that binding, whatever their element type.
     */
    public function __construct(private string $name, private string $type, string $context)
    {
        (new IsNotEmpty())
            ->assert($name);

        (new IsNotEmpty())
            ->assert($type);

        $this->default = new Fallback(
            new Binding(Type::ARRAY, Binding::DEFAULT, $context),
            new Instance([]),
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

    public function getArgument(): Provider
    {
        if ($this->explicit !== null) {
            return $this->explicit;
        }

        return $this->default;
    }

    /**
     * A provider bound here supplies the whole collected array, so its target
     * is checked against `array`, not against the element type.
     */
    public function withArgument(Provider $provider): Parameter
    {
        (new IsAssignableTo(Type::ARRAY))
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
     * Always true: a variadic can replace any kind of base.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function canReplace(Parameter $base): bool
    {
        return true;
    }

    public function canBeOmitted(): bool
    {
        return true;
    }
}
