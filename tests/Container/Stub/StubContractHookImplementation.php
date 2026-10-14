<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Stub;

final class StubContractHookImplementation implements StubContractHook
{
    public int $calls = 0;

    public function m(): void
    {
        $this->calls++;
    }
}
