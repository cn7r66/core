<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Exception;

use InvalidArgumentException;
use Vivarium\Assertion\Failure;

final class AssertionFailed extends InvalidArgumentException
{
    public function __construct(
        private Failure $failure,
        private mixed $value,
    ) {
    }
}
