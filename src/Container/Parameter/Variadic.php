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
     * Resolves the default provider to the container's default `array`
     * binding in this context, falling back to an empty array when nothing
     * is bound.
     *
     * Interim policy: every variadic parameter in a given context collapses
     * onto this same `array` identity regardless of its own element type —
     * a deliberate, temporary limitation (not a design goal) accepted until
     * the Type value-object hierarchy lands and multibinding can give each
     * element type its own `Sequence<T>` identity instead (see
     * `core/.claude/ContainerTheory.md` §6.3 and `core/.claude/TODO.md`
     * Phase 2). At that point this becomes
     * `Binding('Sequence<' . $this->type . '>', Binding::DEFAULT, $context)`.
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

    public function getArgument(): Provider
    {
        if ($this->explicit !== null) {
            return $this->explicit;
        }

        return $this->default;
    }

    /**
     * The guard checks the provider's target against `Type::ARRAY`, not
     * against `$this->type`: `$this->type` holds the element type (e.g.
     * `string` for `string ...$tags`), but a provider bound to a variadic
     * slot promises the collected value — the whole array the slot
     * receives. Interim policy until parameterized types (`Array<Foo>` /
     * `iterable<Foo>`) land.
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
     * Always true: PHP's own override rules permit widening any non-variadic
     * base to a variadic override, and a variadic base can always be
     * replaced by another variadic. There is no base kind a variadic
     * position cannot legally replace.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function canReplace(Parameter $base): bool
    {
        return true;
    }

    /**
     * A variadic position may always be omitted from the target signature
     * it is projected onto.
     */
    public function canBeOmitted(): bool
    {
        return true;
    }
}
