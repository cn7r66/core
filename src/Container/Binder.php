<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container;

use Vivarium\Container\Binding\DecoratorBinder;
use Vivarium\Container\Binding\InjectionBinder;
use Vivarium\Container\Binding\ProviderBinder;
use Vivarium\Container\Binding\ScopeBinder;

interface Binder
{
    public function bind(
        string $type,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): ProviderBinder;

    /** Constructor autowiring for a class; the scope is mandatory. */
    public function define(
        string $class,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): ScopeBinder;

    /** Replaces an existing registration; the scope is mandatory. */
    public function extend(
        string $type,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): ProviderBinder;

    public function inject(
        string $type,
        string $context = Binding::GLOBAL,
    ): InjectionBinder;

    public function call(
        string $type,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): InjectionBinder;

    public function decorate(
        string $type,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): DecoratorBinder;
}
