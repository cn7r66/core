<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Stub;

final class StubDecoratedWrapper implements StubDecoratedSubject
{
    public function __construct(
        public readonly StubDecoratedSubject $inner,
        public readonly StubDecoratedDependency $dependency,
        public readonly int $level = 1,
    ) {
    }
}
