<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Container\Binding;

use ReflectionFunction;
use Vivarium\Assertion\Conditional\IsNotNull;
use Vivarium\Container\Scope;

/** @template T */
final class ScopeBinder
{
    /** @var callable (Scope):T */
    private $create;

    /** @param callable(Scope):T $name */
    public function __construct(callable $create)
    {
        (new IsNotNull())
            ->assert(
                (new ReflectionFunction($create))->getReturnType(),
                '"Missing type hint on callback function."',
            );

        $this->create = $create;
    }

    /** @return T */
    public function transient()
    {
        return ($this->create)(Scope::TRANSIENT);
    }

    /** @return T */
    public function cloneable()
    {
        return ($this->create)(Scope::CLONEABLE);
    }

    /** @return T */
    public function service()
    {
        return ($this->create)(Scope::SERVICE);
    }
}
