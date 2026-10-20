<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Core\Event;

use Vivarium\Assertion\Numeric\IsGreaterOrEqualThan;
use Vivarium\Dispatcher\NonStoppableEvent;

final class AppEnd extends NonStoppableEvent
{
    public function __construct(private int $exitCode)
    {
        (new IsGreaterOrEqualThan(0))
            ->assert($exitCode);
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }
}
