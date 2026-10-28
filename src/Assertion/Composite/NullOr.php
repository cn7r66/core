<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Composite;

use Override;
use Vivarium\Assertion\Assertion;

/** @implements Assertion<mixed> */
final class NullOr implements Assertion
{
    /** @param Assertion<mixed> $assertion */
    public function __construct(private readonly Assertion $assertion)
    {
    }

    #[Override]
    public function assert(mixed $value, string $message = ''): void
    {
        if ($value === null) {
            return;
        }

        $this->assertion->assert($value, $message);
    }

    #[Override]
    public function check(mixed $value): bool
    {
        return $value === null || $this->assertion->check($value);
    }
}
