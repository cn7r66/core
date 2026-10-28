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
use Vivarium\Assertion\Var\IsArray;

/**
 * @template T
 * @implements Assertion<array<T>>
 */
final class Each implements Assertion
{
    /** @param Assertion<T> $assertion */
    public function __construct(private readonly Assertion $assertion)
    {
    }

    #[Override]
    public function assert(mixed $value, string $message = ''): void
    {
        (new IsArray())->assert($value);

        foreach ($value as $element) {
            $this->assertion->assert($element, $message);
        }
    }

    #[Override]
    public function check(mixed $value): bool
    {
        if (! (new IsArray())->check($value)) {
            return false;
        }

        foreach ($value as $element) {
            if (! $this->assertion->check($element)) {
                return false;
            }
        }

        return true;
    }
}
