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

/**
 * @template A
 * @template B
 * @implements Assertion<A&B>
 */
final class Both implements Assertion
{
    /**
     * @param Assertion<A> $first
     * @param Assertion<B> $second
     */
    public function __construct(private readonly Assertion $first, private readonly Assertion $second)
    {
    }

    #[Override]
    public function assert(mixed $value, string $message = ''): void
    {
        $this->first->assert($value, $message);
        $this->second->assert($value, $message);
    }

    #[Override]
    public function check(mixed $value): bool
    {
        return $this->first->check($value) && $this->second->check($value);
    }
}
