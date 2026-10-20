<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Binding;

use Vivarium\Container\Caller;

/** @template T */
final class MethodBinder
{
    /** @var callable(string, callable(Caller):Caller): T */
    private $create;

    public function __construct(callable $create)
    {
        $this->create = $create;
    }

    /** @return T */
    public function method(string $method, callable|null $configure = null)
    {
        if ($configure === null) {
            $configure = static fn (Caller $caller): Caller => $caller;
        }

        return ($this->create)($method, $configure);
    }
}
