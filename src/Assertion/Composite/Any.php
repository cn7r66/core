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

use function array_values;

/** @implements Assertion<mixed> */
final class Any implements Assertion
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
        if ($this->check($value)) {
            return;
        }

        $failure = new Failure(
            'Expected value to satisfy one of {assertions}. Got {value}.',
            $value,
            assertions: $this->assertions,
        );

        throw new AssertionFailed($message === '' ? $failure : $failure->withTemplate($message));
    }

    #[Override]
    public function check(mixed $value): bool
    {
        foreach ($this->assertions as $assertion) {
            if ($assertion->check($value)) {
                return true;
            }
        }

        return false;
    }
}
