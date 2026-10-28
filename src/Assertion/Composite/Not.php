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
use Vivarium\Assertion\Failure;

/** @implements Assertion<mixed> */
final class Not implements Assertion
{
    /** @param Assertion<mixed> $assertion */
    public function __construct(private readonly Assertion $assertion)
    {
    }

    #[Override]
    public function assert(mixed $value, string $message = ''): void
    {
        if ($this->check($value)) {
            return;
        }

        $failure = new Failure(
            'Expected value not to satisfy {assertion}. Got {value}.',
            $value,
            assertion: $this->assertion,
        );

        throw new AssertionFailed($message === '' ? $failure : $failure->withTemplate($message));
    }

    #[Override]
    public function check(mixed $value): bool
    {
        return ! $this->assertion->check($value);
    }
}
