<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Equality;

final class Equal
{
    /**
     * @param mixed $first
     * @param mixed $second
     */
    public static function areEquals($first, $second): bool
    {
        return (new EqualsBuilder())
            ->append($first, $second)
            ->isEquals();
    }

    /** @param mixed $element */
    public static function hash($element): string
    {
        return (new HashBuilder())
            ->append($element)
            ->getHashCode();
    }
}
