<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Type;

use ReflectionClass;
use ReflectionClassConstant;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use Vivarium\Type\Exception\NotAType;

use function array_any;
use function array_filter;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function explode;
use function get_debug_type;
use function in_array;
use function is_object;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function substr;
use function trim;

final class Types
{
    private const array CONTEXTUAL = ['self', 'parent', 'static'];

    public static function fromString(string $type): Type
    {
        return self::parse($type, null, null);
    }

    public static function ofParameter(ReflectionParameter $parameter): Type
    {
        return self::read($parameter->getType(), $parameter->getDeclaringClass(), null);
    }

    public static function ofProperty(ReflectionProperty $property): Type
    {
        return self::read($property->getType(), $property->getDeclaringClass(), null);
    }

    /** @param string $called the class the function is called through, which `static` resolves to */
    public static function ofReturn(ReflectionFunctionAbstract $function, string $called): Type
    {
        $scope = $function instanceof ReflectionMethod ?
            $function->getDeclaringClass() : $function->getClosureScopeClass();

        return self::read($function->getReturnType(), $scope, $called);
    }

    public static function ofClassConstant(ReflectionClassConstant $constant): Type
    {
        if (! $constant->hasType()) {
            return self::of($constant->getValue());
        }

        return self::read($constant->getType(), $constant->getDeclaringClass(), null);
    }

    public static function of(mixed $value): Type
    {
        if (is_object($value)) {
            return new Clazz($value::class);
        }

        $type = get_debug_type($value);
        if (str_starts_with($type, 'resource')) {
            throw new NotAType($type, 'resources have no type');
        }

        return new Primitive($type);
    }

    /**
     * The type of a value that is any of $types, which may overlap: applies the fix for each
     * redundancy PHP would reject, and returns a single type when only one is left. Members are
     * never reduced by subtyping: `int` and `float` both stay.
     */
    public static function anyOf(Type ...$types): Type
    {
        $members = [];
        foreach ($types as $type) {
            $members = array_merge($members, $type instanceof Union ? $type->getMembers() : [$type]);
        }

        $members = self::unique($members);
        if (self::has($members, Primitive::MIXED) && ! self::has($members, Primitive::VOID)) {
            return new Primitive(Primitive::MIXED);
        }

        $members = self::without($members, [Primitive::NEVER]);
        if ($members === []) {
            return new Primitive(Primitive::NEVER);
        }

        $members = self::reduceBool($members);
        $members = self::reduceObject($members);
        $members = self::reduceIntersections($members);
        if (count($members) === 1) {
            return $members[0];
        }

        return new Union(...$members);
    }

    /**
     * The type of a value that is all of $types, which may overlap: duplicates are dropped, and a
     * single class is returned when only one is left.
     */
    public static function allOf(Type ...$types): Type
    {
        $members = [];
        foreach ($types as $type) {
            $members = array_merge($members, $type instanceof Intersection ? $type->getMembers() : [$type]);
        }

        $members = self::unique($members);
        if (count($members) === 1 && $members[0] instanceof Clazz) {
            return $members[0];
        }

        return new Intersection(...$members);
    }

    /** @param ReflectionClass<object>|null $scope */
    private static function read(ReflectionType|null $type, ReflectionClass|null $scope, string|null $called): Type
    {
        if ($type === null) {
            return new Primitive(Primitive::MIXED);
        }

        return self::parse((string) $type, $scope, $called);
    }

    /**
     * @param ReflectionClass<object>|null $scope  the class `self` and `parent` refer to
     * @param string|null                  $called the class `static` refers to
     */
    private static function parse(string $type, ReflectionClass|null $scope, string|null $called): Type
    {
        try {
            $part = trim($type);
            if ($part === '') {
                throw new NotAType($type, 'empty type');
            }

            if (str_starts_with($part, '?')) {
                return new Union(self::parse(substr($part, 1), $scope, $called), new Primitive(Primitive::NULL));
            }

            $parse = static fn (string $member): Type => self::parse($member, $scope, $called);
            if (str_contains($part, '|')) {
                return new Union(...array_map($parse, explode('|', $part)));
            }

            if (str_starts_with($part, '(') && str_ends_with($part, ')')) {
                $part = substr($part, 1, -1);
            }

            if (str_contains($part, '&')) {
                return new Intersection(...array_map($parse, explode('&', $part)));
            }

            return self::name($part, $scope, $called);
        } catch (NotAType $e) {
            throw new NotAType($type, $e->getReason());
        }
    }

    /** @param ReflectionClass<object>|null $scope */
    private static function name(string $name, ReflectionClass|null $scope, string|null $called): Type
    {
        $keyword = strtolower($name);
        if (in_array($keyword, self::CONTEXTUAL, true)) {
            return self::contextual($keyword, $scope, $called);
        }

        if ($keyword === 'iterable') {
            return new Union(new Clazz('Traversable'), new Primitive(Primitive::ARRAY));
        }

        if (in_array($keyword, Primitive::KEYWORDS, true)) {
            return new Primitive($keyword);
        }

        return new Clazz($name);
    }

    /** @param ReflectionClass<object>|null $scope */
    private static function contextual(string $keyword, ReflectionClass|null $scope, string|null $called): Clazz
    {
        if ($keyword === 'static' && $called !== null) {
            return new Clazz($called);
        }

        if ($keyword === 'static' || $scope === null) {
            throw new NotAType($keyword, sprintf('%s cannot be resolved without a class', $keyword));
        }

        if ($keyword === 'self') {
            return new Clazz($scope->getName());
        }

        $parent = $scope->getParentClass();
        if ($parent === false) {
            throw new NotAType($keyword, sprintf('%s has no parent class', $scope->getName()));
        }

        return new Clazz($parent->getName());
    }

    /**
     * @param list<Type> $types
     *
     * @return list<Type>
     */
    private static function unique(array $types): array
    {
        $unique = [];
        foreach ($types as $type) {
            $unique[(string) $type] ??= $type;
        }

        return array_values($unique);
    }

    /** @param list<Type> $types */
    private static function has(array $types, string $name): bool
    {
        return array_any($types, static fn (Type $type): bool => (string) $type === $name);
    }

    /**
     * @param list<Type>   $types
     * @param list<string> $names
     *
     * @return list<Type>
     */
    private static function without(array $types, array $names): array
    {
        return array_values(array_filter(
            $types,
            static fn (Type $type): bool => ! in_array((string) $type, $names, true),
        ));
    }

    /**
     * @param list<Type> $members
     *
     * @return list<Type>
     */
    private static function reduceBool(array $members): array
    {
        if (self::has($members, Primitive::BOOL)) {
            return self::without($members, [Primitive::TRUE, Primitive::FALSE]);
        }

        if (self::has($members, Primitive::TRUE) && self::has($members, Primitive::FALSE)) {
            return [...self::without($members, [Primitive::TRUE, Primitive::FALSE]), new Primitive(Primitive::BOOL)];
        }

        return $members;
    }

    /**
     * @param list<Type> $members
     *
     * @return list<Type>
     */
    private static function reduceObject(array $members): array
    {
        if (! self::has($members, Primitive::OBJECT)) {
            return $members;
        }

        return array_values(array_filter($members, static fn (Type $member): bool => $member instanceof Primitive));
    }

    /**
     * @param list<Type> $members
     *
     * @return list<Type>
     */
    private static function reduceIntersections(array $members): array
    {
        return array_values(array_filter(
            $members,
            static fn (Type $member): bool => ! $member instanceof Intersection
                || ! array_any(
                    $members,
                    static fn (Type $other): bool => $other !== $member
                        && ($other instanceof Clazz || $other instanceof Intersection)
                        && $member->isRedundantWith($other),
                ),
        ));
    }
}
