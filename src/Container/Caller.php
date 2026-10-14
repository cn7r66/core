<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container;

use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Binding\ProviderBinder;

/**
 * Binds an argument provider to a parameter of the underlying callable
 * (a constructor, method or function).
 *
 * Parameters may be addressed by name or by zero-based position. Binding is
 * immutable: completing a binder yields a new instance, the original is
 * never mutated.
 */
interface Caller
{
    /**
     * Binds an argument provider to the parameter named $name.
     *
     * @throws AssertionFailed If the callable has no parameter named $name.
     */
    public function bindArgument(string $name): ProviderBinder;

    /**
     * Binds an argument provider to the parameter at the zero-based
     * $position.
     *
     * A provider bound to a variadic position supplies the whole collected
     * array, not one element.
     *
     * @throws AssertionFailed If $position is out of range.
     */
    public function bindArgumentAtPosition(int $position): ProviderBinder;
}
