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

use function array_values;

/** @implements Assertion<mixed> */
final class All implements Assertion
{
    /** @var list<Assertion<mixed>> */
    private readonly array $assertions;

    /**
     * @param Assertion<mixed> $assertion
     * @param Assertion<mixed> ...$assertions
     */
    public function __construct(Assertion $assertion, Assertion ...$assertions)
    {
        $this->assertions = [$assertion, ...array_values($assertions)];
    }

    #[Override]
    public function assert(mixed $value, string $message = ''): void
    {
        foreach ($this->assertions as $assertion) {
            $assertion->assert($value, $message);
        }
    }

    #[Override]
    public function check(mixed $value): bool
    {
        foreach ($this->assertions as $assertion) {
            if (! $assertion->check($value)) {
                return false;
            }
        }

        return true;
    }
}
