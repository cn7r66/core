<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Stub;

use Override;

final class StubChild extends StubParent
{
    use StubMixin;

    public int $visible = 0;

    public private(set) int $locked = 0;

    #[Override]
    public function described(): void
    {
    }
}
