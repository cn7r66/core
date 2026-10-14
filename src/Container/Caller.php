<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container;

use Vivarium\Container\Binding\ProviderBinder;

/**
 * Binds an argument provider to a parameter of the underlying callable
 * (a constructor, method or function).
 *
 * Parameters may be addressed by name or by position; names are merely an
 * interface onto positions — positions are the source of truth. Binding is
 * immutable: completing a binder yields a new instance, the original is
 * never mutated.
 */
interface Caller
{
    /**
     * Binds an argument provider to the parameter named $name.
     *
     * $name must be a parameter of the underlying callable's declaration; an
     * unknown name is a precondition violation.
     */
    public function bindArgument(string $name): ProviderBinder;

    /**
     * Binds an argument provider to the parameter at the zero-based
     * $position.
     *
     * $position must be within the underlying callable's declared
     * parameters; an out-of-range position is a precondition violation.
     */
    public function bindArgumentAtPosition(int $position): ProviderBinder;
}
