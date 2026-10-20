<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Collection\Util;

use Vivarium\Equality\Equal;

use function is_int;
use function is_string;

final class KeyHash
{
    /** @param mixed $key */
    public static function hash($key): int|string
    {
        return is_int($key) || is_string($key) ? $key : Equal::hash($key);
    }
}
