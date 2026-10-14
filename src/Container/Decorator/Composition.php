<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Decorator;

use Vivarium\Assertion\Var\IsCallable;
use Vivarium\Container\Binding\ProviderBinder;
use Vivarium\Container\Container;
use Vivarium\Container\Decorator;
use Vivarium\Container\Provider;
use Vivarium\Container\Provider\Fun;

use function is_string;

/**
 * Decorates a callable `f` into a new callable equivalent to `g(f(...$args))`.
 *
 * `g` is the given function: the result of `f` is bound to the slot, every
 * other parameter of `g` is resolved from the container at each call.
 */
final class Composition implements Decorator
{
    public function __construct(private Fun $function, private string|int $slot)
    {
        // Fails now, not at first call, when the slot does not exist.
        self::bindSlot($function, $slot);
    }

    public function enhance(mixed $instance, Container $container): mixed
    {
        (new IsCallable())
            ->assert($instance);

        $function = $this->function;
        $slot     = $this->slot;

        return static function (mixed ...$arguments) use ($instance, $function, $slot, $container): mixed {
            $result = $instance(...$arguments);

            return self::bindSlot($function, $slot)
                ->toInstance($result)
                ->provide($container);
        };
    }

    private static function bindSlot(Fun $function, string|int $slot): ProviderBinder
    {
        if (is_string($slot)) {
            return $function->bindArgument($slot);
        }

        return $function->bindArgumentAtPosition($slot);
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    public function accept(Provider $provider): bool
    {
        return true;
    }
}
