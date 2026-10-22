<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Var;

use Override;
use Vivarium\Assertion\Assertion;

/** @implements Assertion<mixed> */
final class IsAnything implements Assertion
{
    #[Override]
    public function assert(mixed $value, string $message = ''): void
    {
    }

    #[Override]
    public function check(mixed $value): bool
    {
        return true;
    }
}
