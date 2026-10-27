<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Type;

use Override;
use ReflectionClass;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;
use Vivarium\Type\Exception\NotAClass;

use function array_all;
use function array_any;
use function class_exists;
use function interface_exists;
use function is_a;

final class Clazz implements Type
{
    /** @var class-string */
    private string $class;

    public function __construct(string $class)
    {
        if (! class_exists($class) && ! interface_exists($class)) {
            throw new NotAClass($class);
        }

        $this->class = self::format($class);
    }

    /**
     * The canonical spelling of a class name: as declared, without a leading backslash. A name
     * that is not a class, interface or enum is returned unchanged.
     *
     * @return ($class is class-string ? class-string : string)
     */
    public static function format(string $class): string
    {
        if (! class_exists($class) && ! interface_exists($class)) {
            return $class;
        }

        return (new ReflectionClass($class))->getName();
    }

    /** @return ReflectionClass<object> */
    public function getReflection(): ReflectionClass
    {
        return new ReflectionClass($this->class);
    }

    #[Override]
    public function accepts(Type $type): bool
    {
        if ($type instanceof Union) {
            return array_all($type->getMembers(), fn (Type $member): bool => $this->accepts($member));
        }

        if ($type instanceof Intersection) {
            return array_any($type->getMembers(), fn (Type $member): bool => $this->accepts($member));
        }

        if ($type instanceof Clazz) {
            return is_a((string) $type, $this->class, true);
        }

        return (string) $type === Primitive::NEVER;
    }

    #[Override]
    public function acceptsVar(mixed $var): bool
    {
        return $var instanceof $this->class;
    }

    #[Override]
    public function __toString(): string
    {
        return $this->class;
    }

    #[Override]
    public function equals(object $object): bool
    {
        if (! $object instanceof Clazz) {
            return false;
        }

        if ($object === $this) {
            return true;
        }

        return (new EqualsBuilder())
            ->append($this->class, $object->class)
            ->isEquals();
    }

    #[Override]
    public function hash(): string
    {
        return (new HashBuilder())
            ->append($this->class)
            ->getHashCode();
    }
}
