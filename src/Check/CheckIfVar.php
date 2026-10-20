<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Check;

/**
 * @method static bool isArray(mixed $var)
 * @method static bool isBoolean(mixed $var)
 * @method static bool isCallable(mixed $var)
 * @method static bool isFloat(mixed $var)
 * @method static bool isInteger(mixed $var)
 * @method static bool isNumeric(mixed $var)
 * @method static bool isObject(mixed $var)
 * @method static bool isString(mixed $var)
 */
final class CheckIfVar
{
    private static Check|null $check = null;

    /** @param array<mixed> $arguments */
    public static function __callStatic(string $name, array $arguments): bool
    {
        if (static::$check === null) {
            static::$check = Check::var();
        }

        return static::$check->__call($name, $arguments);
    }
}
