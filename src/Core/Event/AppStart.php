<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Core\Event;

use Vivarium\Assertion\Numeric\IsGreaterOrEqualThan;
use Vivarium\Dispatcher\Event;

final class AppStart implements Event
{
    private int $exitCode;

    public function __construct()
    {
        $this->exitCode = 0;
    }

    public function isPropagationStopped(): bool
    {
        return $this->exitCode !== 0;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function withExitCode(int $exitCode): self
    {
        (new IsGreaterOrEqualThan(0))
            ->assert($exitCode);

        $appStart           = clone $this;
        $appStart->exitCode = $exitCode;

        return $appStart;
    }
}
