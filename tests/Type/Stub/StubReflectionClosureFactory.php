<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Type\Stub;

use Closure;

class StubReflectionClosureFactory
{
    public function create(): Closure
    {
        return function (self $x): static {
            return $this;
        };
    }
}
