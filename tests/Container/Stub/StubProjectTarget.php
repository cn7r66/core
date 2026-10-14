<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Stub;

final class StubProjectTarget
{
    public function __construct(
        // phpcs:ignore
        private StubService $component,
        // phpcs:ignore
        private string|null $title = null,
        string ...$labels,
    ) {
    }
}
