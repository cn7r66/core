<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Container\Exception;

use RuntimeException;
use Throwable;
use Vivarium\Container\Binding;

use function array_map;
use function implode;

final class ResolutionFailed extends RuntimeException implements ContainerException
{
    /** @param non-empty-list<Binding> $path Bindings being resolved, root first, the one that failed last. */
    public function __construct(private array $path, Throwable $previous)
    {
        parent::__construct(
            'Resolution failed while resolving ' .
            implode(' → ', array_map(static fn (Binding $binding): string => (string) $binding, $path)) .
            ': ' . $previous->getMessage(),
            0,
            $previous,
        );
    }

    /** @return non-empty-list<Binding> */
    public function getPath(): array
    {
        return $this->path;
    }
}
