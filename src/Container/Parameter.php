<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container;

/**
 * Descriptor of one parameter of a callable signature: the declared slot a
 * Provider argument fills.
 */
interface Parameter
{
    /** Returns the parameter's name. */
    public function getName(): string;

    /**
     * Returns the parameter's declared type identity.
     */
    public function getType(): string;

    /**
     * Returns the argument that fills this position: the explicitly bound
     * provider if one was set with withArgument(), otherwise the default
     * provider built for this parameter at construction. Total: every
     * parameter always has an argument.
     */
    public function getArgument(): Provider;

    /**
     * Returns a new Parameter with $provider bound as its explicit argument,
     * replacing any previously bound argument.
     *
     * The provider's target must be assignable to this slot's type; a
     * mismatch is a precondition violation.
     */
    public function withArgument(Provider $provider): Parameter;

    /**
     * True if and only if an explicit argument has been bound via
     * withArgument(), as opposed to this parameter resting on its default.
     */
    public function isExplicit(): bool;

    /**
     * True if and only if a parameter of this kind, at the same position,
     * may legally replace $base when overriding a signature.
     *
     * Mirrors PHP's own parameter-override variance rules.
     */
    public function canReplace(Parameter $base): bool;

    /**
     * True if and only if a target signature is allowed to lack a
     * corresponding base position for a parameter of this kind.
     */
    public function canBeOmitted(): bool;
}
