<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Stub;

final class StubMethodCallSubclass extends StubMethodCallBase
{
    public string $suffix = 'default';

    public function apply(StubService $dependency, string $suffix = 'default'): void
    {
        $this->service = $dependency;
        $this->suffix  = $suffix;
    }
}
