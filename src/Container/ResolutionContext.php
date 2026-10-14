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
 * of bindings currently being resolved (ancestors only, root first).
 *
 * A binding is on the path for the whole duration of its own resolution and leaves
 * it when get() returns, so diamonds are not cycles. Requesting a binding already
 * on the path throws CircularDependency. Any other failure is wrapped in
 * ResolutionFailed carrying the path: the deepest context wraps first, outer
 * contexts rethrow CircularDependency and ResolutionFailed unchanged.
 */
final class ResolutionContext implements Container
{
    /**
     * @param Closure(Binding, Container): mixed $resolve   Resolves the binding using the given
     *                                                      Container as the context for nested calls.
     * @param Container                          $container Target of has().
     * @param list<Binding>                      $path      Bindings being resolved, root first.
     */
    public function __construct(private Closure $resolve, private Container $container, private array $path = [])
    {
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

        foreach ($this->path as $ancestor) {
            if ($ancestor->equals($binding)) {
                throw new CircularDependency([...$this->path, $binding]);
            }
        }

        $context       = clone $this;
        $context->path = [...$this->path, $binding];

        try {
            return ($this->resolve)($binding, $context);
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
