<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container;

use Vivarium\Comparator\ValueAndPriority;
use Vivarium\Container\Exception\BindingNotFound;

interface Registry
{
    public function hasProvider(Binding $binding): bool;

    /**
     * Returns the registered binding $binding resolves to, widening its context.
     *
     * @throws BindingNotFound If nothing is registered for $binding.
     */
    public function locate(Binding $binding): Binding;

    public function findProvider(Binding $binding): Provider;

    public function findScope(Binding $binding): Scope;

    /** @return iterable<ValueAndPriority<Enhancement>> */
    public function findEnhancements(Binding $binding): iterable;
}
