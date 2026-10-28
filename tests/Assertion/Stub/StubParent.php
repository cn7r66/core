<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Stub;

abstract class StubParent implements StubContractA
{
    protected int $shielded = 0;

    private int $hidden = 0;

    public function __construct()
    {
    }

    public function touch(): int
    {
        $this->secret();

        return $this->hidden;
    }

    protected function guarded(): void
    {
    }

    private function secret(): void
    {
    }
}
