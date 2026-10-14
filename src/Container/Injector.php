<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container;

use ReflectionClass;
use Vivarium\Check\CheckIfType;
use Vivarium\Collection\Map\HashMap;
use Vivarium\Collection\Map\Map;
use Vivarium\Container\Cache\NoOpCache;
use Vivarium\Container\Collector\NoOpCollector;
use Vivarium\Container\Definition\Cloneable;
use Vivarium\Container\Definition\Service;
use Vivarium\Container\Definition\Transient;
use Vivarium\Container\Exception\BindingNotFound;
use Vivarium\Container\Provider\Constructor;

final class Injector implements Container
{
    /** @var Map<Binding, Definition> */
    private Map $solved;

    private Cache $cache;

    private Collector $collector;

    public function __construct(private Registry $registry)
    {
        $this->solved    = new HashMap();
        $this->cache     = new NoOpCache();
        $this->collector = new NoOpCollector();
    }

    public function get(Binding|string $request): mixed
    {
        return (new ResolutionContext(
            fn (Binding $binding): Binding => $this->locate($binding),
            fn (Binding $binding, Container $context): mixed => $this->resolve($binding, $context),
            $this,
        ))->get($request);
    }

    private function resolve(Binding $key, Container $context): mixed
    {
        if ($this->cache->lookup($key)) {
            $this->solved = $this->solved->put(
                $key,
                $this->cache->restore($key),
            );
        }

        if ($this->solved->containsKey($key)) {
            return $this->solved
                ->get($key)
                ->solve($context);
        }

        $provider   = $this->getProvider($key);
        $definition = $this->applyScope($key, $provider);
        $definition = $this->applyEnhancements($key, $provider, $definition);

        $this->solved = $this->solved->put($key, $definition);

        return $definition->solve($context);
    }

    public function has(Binding|string $request): bool
    {
        $binding = $this->makeBinding($request);

        if ($this->solved->containsKey($binding)) {
            return true;
        }

        if ($this->cache->lookup($binding)) {
            return true;
        }

        if ($this->registry->hasProvider($binding)) {
            return true;
        }

        if ($binding->getTag() !== Binding::DEFAULT) {
            return false;
        }

        if (! CheckIfType::isClass($binding->getType())) {
            return false;
        }

        return (new ReflectionClass($binding->getType()))
            ->isInstantiable();
    }

    public function withCache(Cache $cache): self
    {
        $container        = clone $this;
        $container->cache = $cache;

        return $container;
    }

    public function withCollector(Collector $collector): self
    {
        $container            = clone $this;
        $container->collector = $collector;

        return $container;
    }

    private function makeBinding(Binding|string $request): Binding
    {
        if ($request instanceof Binding) {
            return $request;
        }

        return new Binding($request);
    }

    private function locate(Binding $binding): Binding
    {
        if ($this->registry->hasProvider($binding)) {
            return $this->registry->locate($binding);
        }

        if ($binding->getTag() === Binding::DEFAULT && CheckIfType::isClass($binding->getType())) {
            return new Binding($binding->getType(), Binding::DEFAULT, Binding::GLOBAL);
        }

        throw new BindingNotFound($binding);
    }

    private function getProvider(Binding $key): Provider
    {
        if ($this->registry->hasProvider($key)) {
            return $this->registry->findProvider($key);
        }

        return new Constructor($key->getType());
    }

    private function applyScope(Binding $binding, Provider $provider): Definition
    {
        return match ($this->registry->findScope($binding)) {
            Scope::SERVICE   => new Service($provider),
            Scope::CLONEABLE => new Cloneable($provider),
            Scope::TRANSIENT => new Transient($provider),
        };
    }

    private function applyEnhancements(Binding $binding, Provider $provider, Definition $definition): Definition
    {
        foreach ($this->registry->findEnhancements($binding) as $entry) {
            if (! $entry->getValue()->accept($provider)) {
                continue;
            }

            $definition = $definition->withEnhancement(
                $entry->getValue(),
                $entry->getPriority(),
            );
        }

        return $definition;
    }
}
