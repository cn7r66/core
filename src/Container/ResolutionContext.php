<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container;

use Closure;
use Throwable;
use Vivarium\Container\Exception\CircularDependency;
use Vivarium\Container\Exception\ResolutionFailed;

/**
 * Container decorator handed down during resolution, carrying the immutable path
 * of registered bindings currently being resolved (ancestors only, root first).
 * A request is mapped to the binding it resolves to before the path is consulted.
 *
 * A binding is on the path for the whole duration of its own resolution and leaves
 * it when get() returns, so diamonds are not cycles. Requesting a binding that
 * resolves to one already on the path throws CircularDependency. Any other failure is wrapped in
 * ResolutionFailed carrying the path: the deepest context wraps first, outer
 * contexts rethrow CircularDependency and ResolutionFailed unchanged.
 */
final class ResolutionContext implements Container
{
    /**
     * @param Closure(Binding): Binding          $locate    Maps a requested binding to the binding it
     *                                                      resolves to; may throw.
     * @param Closure(Binding, Container): mixed $resolve   Resolves the located binding using the given
     *                                                      Container as the context for nested calls.
     * @param Container                          $container Target of has().
     * @param list<Binding>                      $path      Located bindings being resolved, root first.
     */
    public function __construct(
        private Closure $locate,
        private Closure $resolve,
        private Container $container,
        private array $path = [],
    ) {
    }

    /**
     * Resolves the request with this context's path extended by the requested binding.
     * A string request is the Binding of that type with default tag and global context.
     *
     * @throws CircularDependency If the binding is already on the path.
     * @throws ResolutionFailed   If resolution fails; carries the path with the failing binding last.
     */
    public function get(Binding|string $request): mixed
    {
        $binding = $request instanceof Binding ? $request : new Binding($request);

        try {
            $key = ($this->locate)($binding);

            foreach ($this->path as $ancestor) {
                if ($ancestor->equals($key)) {
                    throw new CircularDependency([...$this->path, $key]);
                }
            }

            $context       = clone $this;
            $context->path = [...$this->path, $key];

            return ($this->resolve)($key, $context);
        } catch (CircularDependency | ResolutionFailed $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ResolutionFailed([...$this->path, $binding], $e);
        }
    }

    /** Delegates to the wrapped container; the path plays no role. */
    public function has(Binding|string $request): bool
    {
        return $this->container->has($request);
    }
}
