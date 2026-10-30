<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Equality;

use function array_keys;
use function is_array;
use function is_float;
use function is_nan;
use function is_object;

final class EqualsBuilder
{
    private bool $isEquals;

    public function __construct()
    {
        $this->isEquals = true;
    }

    /**
     * @param mixed $first
     * @param mixed $second
     */
    public function append($first, $second): EqualsBuilder
    {
        if (! $this->isEquals) {
            return $this;
        }

        if (is_object($first) && is_object($second)) {
            return $this->appendObject($first, $second);
        }

        if (is_array($first) && is_array($second)) {
            return $this->appendEach($first, $second);
        }

        if (is_float($first) && is_float($second)) {
            return $this->appendFloat($first, $second);
        }

        return $this->withEquals($first === $second);
    }

    public function isEquals(): bool
    {
        return $this->isEquals;
    }

    /**
     * An Equality is never equal to an object without one: their hashes could not agree.
     * Both sides must agree, so the result does not depend on the argument order.
     */
    private function appendObject(object $first, object $second): EqualsBuilder
    {
        if (! $first instanceof Equality || ! $second instanceof Equality) {
            return $this->withEquals($first === $second);
        }

        return $this->withEquals($first->equals($second) && $second->equals($first));
    }

    /**
     * @param array<mixed> $first
     * @param array<mixed> $second
     */
    private function appendEach(array $first, array $second): EqualsBuilder
    {
        if (array_keys($first) !== array_keys($second)) {
            return $this->withEquals(false);
        }

        $builder = $this;
        foreach ($first as $key => $value) {
            $builder = $builder->append($value, $second[$key]);
        }

        return $builder;
    }

    private function appendFloat(float $first, float $second): EqualsBuilder
    {
        return $this->withEquals($first === $second || (is_nan($first) && is_nan($second)));
    }

    private function withEquals(bool $isEquals): EqualsBuilder
    {
        $builder           = clone $this;
        $builder->isEquals = $isEquals;

        return $builder;
    }
}
