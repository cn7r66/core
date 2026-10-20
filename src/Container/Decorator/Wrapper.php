<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Decorator;

use Vivarium\Assertion\Object\IsInstanceOf;
use Vivarium\Container\Binding\ProviderBinder;
use Vivarium\Container\Caller;
use Vivarium\Container\Container;
use Vivarium\Container\Decorator;
use Vivarium\Container\Provider;

use function is_int;

/**
 * Decorates an instance by passing it to a callable that wraps it.
 *
 * The decorated object is bound to the parameter identified by the slot
 * (a name, or a zero-based position); every other parameter is resolved
 * through the container the decoration runs in. The result of the callable
 * replaces the instance.
 */
final class Wrapper implements Decorator
{
    /**
     * @param Caller&Provider $caller Builds the wrapper.
     * @param string|int      $slot   Parameter receiving the decorated object,
     *                                by name or zero-based position.
     */
    public function __construct(private Caller&Provider $caller, private string|int $slot)
    {
        // Binding an unknown slot throws, so a bad slot fails at registration.
        $this->bindSlot();
    }

    public function enhance(mixed $instance, Container $container): mixed
    {
        $caller = $this->bindSlot()->toInstance($instance);

        (new IsInstanceOf(Provider::class))->assert($caller);

        return $caller->provide($container);
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    public function accept(Provider $provider): bool
    {
        return true;
    }

    /** @return ProviderBinder<mixed> */
    private function bindSlot(): ProviderBinder
    {
        return is_int($this->slot)
            ? $this->caller->bindArgumentAtPosition($this->slot)
            : $this->caller->bindArgument($this->slot);
    }
}
