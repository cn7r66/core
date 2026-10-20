<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Stub;

final class StubWithVariadicArgs
{
    /** @var array<int, string> */
    public readonly array $names;

    public function __construct(string ...$names)
    {
        $this->names = $names;
    }
}
