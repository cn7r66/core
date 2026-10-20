<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Check;

/**
 * @method static bool implementsInterface(string $type, string $interface)
 * @method static bool isAssignableTo(string $type, string $target)
 * @method static bool isAssignableToClass(string $type, string $class)
 * @method static bool isAssignableToIntersection(string $type, string $intersection)
 * @method static bool isAssignableToPrimitive(string $type, string $primitive)
 * @method static bool isAssignableToUnion(string $type, string $union)
 * @method static bool isSubclassOf(string $type, string $class)
 * @method static bool isBasicType(string $type)
 * @method static bool isClass(string $type)
 * @method static bool isClassOrInterface(string $type)
 * @method static bool isInterface(string $type)
 * @method static bool isIntersection(string $type)
 * @method static bool isNamespace(string $type)
 * @method static bool isPrimitive(string $type)
 * @method static bool isType(string $type)
 * @method static bool isUnion(string $type)
 */
final class CheckIfType
{
    private static Check|null $check = null;

    /** @param array<mixed> $arguments */
    public static function __callStatic(string $name, array $arguments): bool
    {
        if (static::$check === null) {
            static::$check = Check::type();
        }

        return static::$check->__call($name, $arguments);
    }
}
