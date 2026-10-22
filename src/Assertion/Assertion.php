<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion;

use Vivarium\Assertion\Exception\AssertionFailed;

/** @template-covariant T */
interface Assertion
{
    /**
     * @throws AssertionFailed
     *
     * @psalm-assert T $value
     */
    public function assert(mixed $value, string $message = ''): void;

    /** @psalm-assert-if-true T $value */
    public function check(mixed $value): bool;
}
