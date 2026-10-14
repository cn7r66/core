<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Binding;

/** @template T */
final class PriorityBinder
{
    /** @var callable(int): T */
    private $create;

    public function __construct(callable $create, private int $defaultPriority)
    {
        $this->create = $create;
    }

    /** @return T */
    public function withDefaultPriority()
    {
        return $this->withPriority($this->defaultPriority);
    }

    /** @return T */
    public function withPriority(int $priority)
    {
        return ($this->create)($priority);
    }
}
