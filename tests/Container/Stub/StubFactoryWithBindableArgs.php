<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Stub;

final class StubFactoryWithBindableArgs
{
    public function create(string $name = 'default'): string
    {
        return $name;
    }

    /** @return array<int, string> */
    public function createVariadic(string ...$names): array
    {
        return $names;
    }
}
