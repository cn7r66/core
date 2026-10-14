<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Registry;

use Vivarium\Assertion\Boolean\IsTrue;
use Vivarium\Collection\Map\HashMap;
use Vivarium\Collection\Map\Map;
use Vivarium\Collection\MultiMap\MultiMap;
use Vivarium\Collection\MultiMap\MultiValueMap;
use Vivarium\Collection\Queue\PriorityQueue;
use Vivarium\Collection\Set\SortedSet;
use Vivarium\Comparator\SortableComparator;
use Vivarium\Comparator\ValueAndPriority;
use Vivarium\Container\Binder;
use Vivarium\Container\Binding;
use Vivarium\Container\Binding\DecoratorBinder;
use Vivarium\Container\Binding\InjectionBinder;
use Vivarium\Container\Binding\ProviderBinder;
use Vivarium\Container\Binding\ScopeBinder;
use Vivarium\Container\Decorator;
use Vivarium\Container\Exception\BindingNotFound;
use Vivarium\Container\Injection;
use Vivarium\Container\Provider;
use Vivarium\Container\Registry;
use Vivarium\Container\Scope;

use function sprintf;

final class EagerRegistry implements Registry, Binder
{
    /** @var Map<Binding, Provider> */
    private Map $providers;

    /** @var MultiMap<Binding, ValueAndPriority<Injection>> */
    private MultiMap $injections;

    /** @var MultiMap<Binding, ValueAndPriority<Injection>> */
    private MultiMap $interceptions;

    /** @var MultiMap<Binding, ValueAndPriority<Decorator>> */
    private MultiMap $decorators;

    /** @var Map<Binding, Scope> */
    private Map $scopes;

    public function __construct()
    {
        $this->providers = new HashMap();

        $this->injections = new MultiValueMap(static function (): PriorityQueue {
            return new PriorityQueue(new SortableComparator());
        });

        $this->interceptions = new MultiValueMap(static function (): PriorityQueue {
            return new PriorityQueue(new SortableComparator());
        });

        $this->decorators = new MultiValueMap(static function (): SortedSet {
            return new SortedSet(new SortableComparator());
        });

        $this->scopes = new HashMap();
    }

    /** @return ProviderBinder<EagerRegistry> */
    public function bind(
        string $type,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): ProviderBinder {
        $binding = new Binding($type, $tag, $context);

        return new ProviderBinder($binding, function (Binding $source, Provider $provider): EagerRegistry {
            $registry            = clone $this;
            $registry->providers = $registry->providers->put($source, $provider);

            return $registry;
        });
    }

    public function define(
        string $class,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): Registry {
        return $this->bind($class, $tag, $context)->toConstructor();
    }

    /** @return ProviderBinder<Registry,Provider> */
    public function extend(
        string $type,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): ProviderBinder {
        $binding = new Binding($type, $tag, $context);

        (new IsTrue())
            ->assert(
                $this->providers->containsKey($binding),
                sprintf('Binding (%s, %s, %s) does not exists.', $type, $tag, $context),
            );

        return new ProviderBinder(
            $binding,
            function (Binding $source, Provider $provider): EagerRegistry {
                $registry            = clone $this;
                $registry->providers = $registry->providers->put($source, $provider);

                return $registry;
            },
        );
    }

    /** @return ScopeBinder<Registry> */
    public function scope(
        string $type,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): ScopeBinder {
        $binding = new Binding($type, $tag, $context);

        return new ScopeBinder(function (Scope $scope) use ($binding): EagerRegistry {
            $registry         = clone $this;
            $registry->scopes = $registry->scopes->put($binding, $scope);

            return $registry;
        });
    }

    /** @return DecoratorBinder<Registry> */
    public function decorate(
        string $type,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): DecoratorBinder {
        $binding = new Binding($type, $tag, $context);

        return new DecoratorBinder(function (Decorator $decorator, int $priority) use ($binding): EagerRegistry {
            $registry             = clone $this;
            $registry->decorators = $registry->decorators->put(
                $binding,
                new ValueAndPriority(
                    $decorator,
                    $priority,
                ),
            );

            return $registry;
        });
    }

    /** @return InjectionBinder<EagerRegistry> */
    public function inject(
        string $type,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): InjectionBinder {
        $binding = new Binding($type, $tag, $context);

        return new InjectionBinder(
            $binding,
            function (Injection $injection, int $priority) use ($binding): EagerRegistry {
                $registry             = clone $this;
                $registry->injections = $registry->injections->put(
                    $binding,
                    new ValueAndPriority($injection, $priority),
                );

                return $registry;
            },
        );
    }

    /** @return InjectionBinder<EagerRegistry> */
    public function call(
        string $type,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): InjectionBinder {
        $binding = new Binding($type, $tag, $context);

        return new InjectionBinder(
            $binding,
            function (Injection $injection, int $priority) use ($binding): EagerRegistry {
                $registry                = clone $this;
                $registry->interceptions = $registry->interceptions->put(
                    $binding,
                    new ValueAndPriority($injection, $priority),
                );

                return $registry;
            },
        );
    }

    public function hasProvider(Binding $binding): bool
    {
        if ($this->providers->containsKey($binding)) {
            return true;
        }

        if ($binding->couldBeWidened()) {
            return $this->hasProvider($binding->widen());
        }

        return false;
    }

    public function findProvider(Binding $binding): Provider
    {
        if ($this->providers->containsKey($binding)) {
            return $this->providers->get($binding);
        }

        if ($binding->couldBeWidened()) {
            return $this->findProvider($binding->widen());
        }

        throw new BindingNotFound($binding);
    }

    public function findScope(Binding $binding): Scope
    {
        if ($this->scopes->containsKey($binding)) {
            return $this->scopes->get($binding);
        }

        return Scope::TRANSIENT;
    }

    /** @return iterable<ValueAndPriority<Enhancement>> */
    public function findEnhancements(Binding $binding): iterable
    {
        $enhancements = new PriorityQueue(new SortableComparator());

        $seen = [];
        foreach ($binding->hierarchy() as $candidate) {
            if (! $this->injections->containsKey($candidate)) {
                continue;
            }

            foreach ($this->injections->get($candidate) as $entry) {
                $slot = $entry->getValue()->getSlot();
                if (isset($seen[$slot])) {
                    continue;
                }

                $seen[$slot]  = true;
                $enhancements = $enhancements->enqueue($entry);
            }
        }

        if ($this->interceptions->containsKey($binding)) {
            foreach ($this->interceptions->get($binding) as $entry) {
                $enhancements = $enhancements->enqueue($entry);
            }
        }

        if ($this->decorators->containsKey($binding)) {
            foreach ($this->decorators->get($binding) as $entry) {
                $enhancements = $enhancements->enqueue($entry);
            }
        }

        return $enhancements;
    }
}
