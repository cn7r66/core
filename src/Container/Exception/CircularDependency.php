<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Container\Exception;

use RuntimeException;
use Vivarium\Assertion\Boolean\IsTrue;
use Vivarium\Container\Binding;

use function array_map;
use function array_slice;
use function count;
use function implode;

final class CircularDependency extends RuntimeException implements ContainerException
{
    /** @param non-empty-list<Binding> $path Resolution path including the repeated binding as last element. */
    public function __construct(private array $path)
    {
        $last  = count($path) - 1;
        $start = null;

        for ($i = 0; $i < $last; $i++) {
            if ($path[$i]->equals($path[$last])) {
                $start = $i;
                break;
            }
        }

        (new IsTrue())->assert(
            $start !== null,
            'Expected last binding of the path to repeat an earlier one. Got %s.',
        );

        parent::__construct(
            'Circular dependency: ' . self::render(array_slice($path, 0, $start)) .
            ($start === 0 ? '' : ' → ') .
            '[' . self::render(array_slice($path, $start)) . ']',
        );
    }

    /** @return non-empty-list<Binding> */
    public function getPath(): array
    {
        return $this->path;
    }

    /** @param list<Binding> $bindings */
    private static function render(array $bindings): string
    {
        return implode(' → ', array_map(static fn (Binding $binding): string => (string) $binding, $bindings));
    }
}
