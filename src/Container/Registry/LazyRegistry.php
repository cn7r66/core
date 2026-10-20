<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Registry;

use Vivarium\Comparator\ValueAndPriority;
use Vivarium\Container\Binding;
use Vivarium\Container\Decorator;
use Vivarium\Container\Injection;
use Vivarium\Container\Provider;
use Vivarium\Container\Registry;
use Vivarium\Container\Scope;

final class LazyRegistry implements Registry
{
    private Registry|null $registry;

    /** @var callable */
    private $factory;

    public function __construct(callable $factory)
    {
        $this->registry = null;
        $this->factory  = $factory;
    }

    public function hasProvider(Binding $binding): bool
    {
        return $this
            ->getRealRegistry()
            ->hasProvider($binding);
    }

    public function locate(Binding $binding): Binding
    {
        return $this
            ->getRealRegistry()
            ->locate($binding);
    }

    public function findProvider(Binding $binding): Provider
    {
        return $this
            ->getRealRegistry()
            ->findProvider($binding);
    }

    public function findScope(Binding $binding): Scope
    {
        return $this
            ->getRealRegistry()
            ->findScope($binding);
    }

    /** @return iterable<ValueAndPriority<Injection>> */
    public function findInjections(Binding $binding): iterable
    {
        return $this
            ->getRealRegistry()
            ->findInjections($binding);
    }

    /** @return iterable<ValueAndPriority<Injection>> */
    public function findCalls(Binding $binding): iterable
    {
        return $this
            ->getRealRegistry()
            ->findCalls($binding);
    }

    /** @return iterable<ValueAndPriority<Decorator>> */
    public function findDecorations(Binding $binding): iterable
    {
        return $this
            ->getRealRegistry()
            ->findDecorations($binding);
    }

    private function getRealRegistry(): Registry
    {
        if ($this->registry === null) {
            $this->registry = ($this->factory)();
        }

        return $this->registry;
    }
}
