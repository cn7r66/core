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
use Vivarium\Assertion\Exception\AssertionFailed;

/**
 * @template T
 * @implements Assertion<T>
 */
final class Redacted implements Assertion
{
    /** @param Assertion<T> $assertion */
    public function __construct(private readonly Assertion $assertion)
    {
    }

    #[Override]
    public function assert(mixed $value, string $message = ''): void
    {
        try {
            $this->assertion->assert($value, $message);
        } catch (AssertionFailed $failed) {
            throw new AssertionFailed($failed->getFailure()->redacted());
        }
    }

    #[Override]
    public function check(mixed $value): bool
    {
        return $this->assertion->check($value);
    }
}
