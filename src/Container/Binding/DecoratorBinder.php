<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Binding;

use Vivarium\Container\Decorator;

/** @template T */
final class DecoratorBinder
{
    /** @var callable(Decorator, int): T */
    private $create;

    public function __construct(callable $create, private int $defaultPriority)
    {
        $this->create = $create;
    }

    /** @return PriorityBinder<T> */
    public function withDecorator(Decorator $decorator): PriorityBinder
    {
        $create = $this->create;

        return new PriorityBinder(
            static fn (int $priority) => $create($decorator, $priority),
            $this->defaultPriority,
        );
    }
}
