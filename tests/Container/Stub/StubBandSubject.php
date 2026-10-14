<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Stub;

final class StubBandSubject
{
    /** @var array<string> */
    public array $log = [];

    public function markInjection(): void
    {
        $this->log[] = 'injection';
    }

    public function markCall(): void
    {
        $this->log[] = 'call';
    }
}
