<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container;

use Vivarium\Assertion\Exception\AssertionFailed;

/**
 * Descriptor of one parameter of a callable signature: the declared slot a
 * Provider argument fills.
 */
interface Parameter
{
    public function getName(): string;

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
     * @throws AssertionFailed If the provider's target is not assignable to
     *                         this slot's type.
     */
    public function withArgument(Provider $provider): Parameter;

    /**
     * Whether an argument was bound with withArgument(), as opposed to this
     * parameter resting on its default.
     */
    public function isExplicit(): bool;

    /**
     * Whether this parameter can replace $base at the same position in an
     * override, following PHP's own parameter variance rules.
     */
    public function canReplace(Parameter $base): bool;

    /**
     * Whether a target signature may lack the base position this parameter
     * occupies.
     */
    public function canBeOmitted(): bool;
}
