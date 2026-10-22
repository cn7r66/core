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

/**
 * @template-covariant T
 * @template D
 * @implements Assertion<T>
 */
abstract class BaseAssertion implements Assertion
{
    /**
     * @throws AssertionFailed
     *
     * @psalm-assert T $value
     */
    #[Override]
    final public function assert(mixed $value, string $message = ''): void
    {
        $this
            ->precondition()
            ->assert($value);

        if (! $this->condition($value)) {
            $failure = $this->failure();

            throw new AssertionFailed(
                $message === '' ? $failure : $failure->withTemplate($message),
                $value,
            );
        }
    }

    /** @psalm-assert-if-true T $value */
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

    abstract protected function failure(): Failure;
}
