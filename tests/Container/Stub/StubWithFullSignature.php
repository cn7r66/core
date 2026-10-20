<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Stub;

final class StubWithFullSignature
{
    public function __construct(
        // phpcs:ignore
        private StubService $service,
        // phpcs:ignore
        private string|null $name = null,
        string ...$tags,
    ) {
    }
}
