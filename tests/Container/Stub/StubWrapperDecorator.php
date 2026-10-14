<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Stub;

final class StubWrapperDecorator implements StubWrapperSubject
{
    public function __construct(
        public readonly StubWrapperSubject $inner,
        public readonly StubWrapperDependency $dep,
    ) {
    }
}
