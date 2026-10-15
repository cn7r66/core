<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Stub;

final class StubInjectorDiamondTop
{
    public function __construct(
        public readonly StubInjectorSharedUserB $sharedUserB,
        public readonly StubInjectorSharedUserC $sharedUserC,
    ) {
    }
}
