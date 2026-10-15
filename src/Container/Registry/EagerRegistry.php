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
use Vivarium\Comparator\Priority;
use Vivarium\Comparator\SortableComparator;
use Vivarium\Comparator\ValueAndPriority;
use Vivarium\Container\Binder;
use Vivarium\Container\Binding;
use Vivarium\Container\Binding\DecoratorBinder;
use Vivarium\Container\Binding\InjectionBinder;
use Vivarium\Container\Binding\ProviderBinder;
use Vivarium\Container\Binding\ScopeBinder;
use Vivarium\Container\Decorator;
use Vivarium\Container\Exception\AmbiguousInjection;
use Vivarium\Container\Exception\BindingNotFound;
use Vivarium\Container\Exception\DuplicateBinding;
use Vivarium\Container\Exception\DuplicateInjection;
use Vivarium\Container\Injection;
use Vivarium\Container\Provider;
use Vivarium\Container\Registry;
use Vivarium\Container\Scope;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function is_a;
use function str_starts_with;

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

        $this->decorators = new MultiValueMap(static function (): PriorityQueue {
            return new PriorityQueue(new SortableComparator());
        });

        $this->scopes = new HashMap();
    }

    /** @return ProviderBinder<ScopeBinder<EagerRegistry>> */
    public function bind(
        string $type,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): ProviderBinder {
        $binding = new Binding($type, $tag, $context);

        if ($this->providers->containsKey($binding)) {
            throw new DuplicateBinding($binding);
        }

        return new ProviderBinder($binding, $this->scopedRegistration());
    }

    /**
     * Constructor autowiring for a class; the scope is mandatory.
     *
     * @return ScopeBinder<EagerRegistry>
     */
    public function define(
        string $class,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): ScopeBinder {
        return $this->bind($class, $tag, $context)->toConstructor();
    }

    /** @return ProviderBinder<ScopeBinder<EagerRegistry>> */
    public function extend(
        string $type,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): ProviderBinder {
        $binding = new Binding($type, $tag, $context);

        (new IsTrue())
            ->assert(
                $this->providers->containsKey($binding),
                'Binding ' . $binding . ' is not registered. Use bind() to register it.',
            );

        return new ProviderBinder($binding, $this->scopedRegistration());
    }

    /** @return callable(Binding, Provider): ScopeBinder<EagerRegistry> */
    private function scopedRegistration(): callable
    {
        return function (Binding $source, Provider $provider): ScopeBinder {
            return new ScopeBinder(
                function (Scope $scope) use ($source, $provider): EagerRegistry {
                    $registry            = clone $this;
                    $registry->providers = $registry->providers->put($source, $provider);
                    $registry->scopes    = $registry->scopes->put($source, $scope);

                    return $registry;
                },
            );
        };
    }

    /** @return DecoratorBinder<Registry> */
    public function decorate(
        string $type,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): DecoratorBinder {
        $binding = new Binding($type, $tag, $context);

        return new DecoratorBinder(
            $binding,
            function (Decorator $decorator, int $priority) use ($binding): EagerRegistry {
                $registry             = clone $this;
                $registry->decorators = $registry->decorators->put(
                    $binding,
                    new ValueAndPriority(
                        $decorator,
                        $priority,
                    ),
                );

                return $registry;
            },
            Priority::HIGH,
        );
    }

    /** @return InjectionBinder<EagerRegistry> */
    public function inject(
        string $type,
        string $context = Binding::GLOBAL,
    ): InjectionBinder {
        $binding = new Binding($type, Binding::DEFAULT, $context);

        return new InjectionBinder(
            $binding,
            function (Injection $injection, int $priority) use ($binding): EagerRegistry {
                if ($this->injections->containsKey($binding)) {
                    foreach ($this->injections->get($binding) as $entry) {
                        if ($entry->getValue()->getSlot() === $injection->getSlot()) {
                            throw new DuplicateInjection($binding, $injection->getSlot());
                        }
                    }
                }

                $registry             = clone $this;
                $registry->injections = $registry->injections->put(
                    $binding,
                    new ValueAndPriority($injection, $priority),
                );

                return $registry;
            },
            Priority::LOW,
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
            Priority::NORMAL,
        );
    }

    public function hasProvider(Binding $binding): bool
    {
        return $this->registeredBinding($binding) !== null;
    }

    public function locate(Binding $binding): Binding
    {
        $registered = $this->registeredBinding($binding);

        if ($registered === null) {
            throw new BindingNotFound($binding);
        }

        return $registered;
    }

    public function findProvider(Binding $binding): Provider
    {
        return $this->providers->get($this->locate($binding));
    }

    private function registeredBinding(Binding $binding): Binding|null
    {
        if ($this->providers->containsKey($binding)) {
            return $binding;
        }

        if ($binding->couldBeWidened()) {
            return $this->registeredBinding($binding->widen());
        }

        return null;
    }

    public function findScope(Binding $binding): Scope
    {
        if ($this->scopes->containsKey($binding)) {
            return $this->scopes->get($binding);
        }

        return Scope::TRANSIENT;
    }

    /** @return iterable<ValueAndPriority<Injection>> */
    public function findInjections(Binding $binding): iterable
    {
        $bySlot   = [];
        $untagged = new Binding($binding->getType(), Binding::DEFAULT, $binding->getContext());
        foreach ($untagged->hierarchy() as $candidate) {
            if (! $this->injections->containsKey($candidate)) {
                continue;
            }

            foreach ($this->injections->get($candidate) as $entry) {
                $slot = $entry->getValue()->getSlot();

                $bySlot[$slot][$candidate->hash()] = [$candidate, $entry];
            }
        }

        $injections = new PriorityQueue(new SortableComparator());
        foreach ($bySlot as $slot => $candidates) {
            $candidates = array_values($candidates);
            $winners    = array_values(array_filter(
                $candidates,
                static function (array $one) use ($candidates): bool {
                    foreach ($candidates as $other) {
                        if (self::isMoreSpecific($other[0], $one[0])) {
                            return false;
                        }
                    }

                    return true;
                },
            ));

            if (count($winners) > 1) {
                throw new AmbiguousInjection(
                    (string) $slot,
                    array_map(static fn (array $winner): Binding => $winner[0], $winners),
                );
            }

            $injections = $injections->enqueue($winners[0][1]);
        }

        return $injections;
    }

    private static function isMoreSpecific(Binding $first, Binding $second): bool
    {
        if ($first->getType() !== $second->getType()) {
            return is_a($first->getType(), $second->getType(), true);
        }

        $context = $second->getContext();

        return $first->getContext() !== $context
            && ($context === Binding::GLOBAL || str_starts_with($first->getContext(), $context . '\\'));
    }

    /** @return iterable<ValueAndPriority<Injection>> */
    public function findCalls(Binding $binding): iterable
    {
        $calls = new PriorityQueue(new SortableComparator());

        if ($this->interceptions->containsKey($binding)) {
            foreach ($this->interceptions->get($binding) as $entry) {
                $calls = $calls->enqueue($entry);
            }
        }

        return $calls;
    }

    /** @return iterable<ValueAndPriority<Decorator>> */
    public function findDecorations(Binding $binding): iterable
    {
        $decorations = new PriorityQueue(new SortableComparator());

        if ($this->decorators->containsKey($binding)) {
            foreach ($this->decorators->get($binding) as $entry) {
                $decorations = $decorations->enqueue($entry);
            }
        }

        return $decorations;
    }
}
