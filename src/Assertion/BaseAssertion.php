<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion;

use Override;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Var\IsAnything;
use Vivarium\Failure\Failure;

/**
 * @template-covariant T
 * @template D
 * @implements Assertion<T>
 * @SuppressWarnings("PHPMD.NumberOfChildren")
 */
abstract class BaseAssertion implements Assertion
{
    /**
     * @throws AssertionFailed
     *
     * @phpstan-assert T $value
     */
    #[Override]
    final public function assert(mixed $value, string $message = ''): void
    {
        $this
            ->precondition()
            ->assert($value);

        if (! $this->condition($value)) {
            $failure = $this->failure($value);

            throw new AssertionFailed($message === '' ? $failure : $failure->withTemplate($message));
        }
    }

    /** @phpstan-assert-if-true T $value */
    #[Override]
    final public function check(mixed $value): bool
    {
        return $this->precondition()->check($value)
            && $this->condition($value);
    }

    /** @return Assertion<D> */
    protected function precondition(): Assertion
    {
        return new IsAnything();
    }

    /** @param D $value */
    abstract protected function condition(mixed $value): bool;

    /** @param D $value */
    abstract protected function failure(mixed $value): Failure;
}
