<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Type;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Vivarium\Type\Exception\NoSuchClassConstant;
use Vivarium\Type\Exception\NoSuchConstant;
use Vivarium\Type\Exception\NoSuchParameter;
use Vivarium\Type\Exception\NotAType;
use Vivarium\Type\Exception\UnsupportedReflectionType;

use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function constant;
use function count;
use function defined;
use function explode;
use function gettype;
use function implode;
use function in_array;
use function interface_exists;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function str_contains;

final class Type
{
    public const string INT      = 'int';
    public const string FLOAT    = 'float';
    public const string STRING   = 'string';
    public const string BOOL     = 'bool';
    public const string ARRAY    = 'array';
    public const string OBJECT   = 'object';
    public const string CALLABLE = 'callable';
    public const string MIXED    = 'mixed';
    public const string NULL     = 'null';
    public const string NEVER    = 'never';
    public const string VOID     = 'void';

    private const array ALIASES = [
        'integer' => self::INT,
        'double'  => self::FLOAT,
        'boolean' => self::BOOL,
        'NULL'    => self::NULL,
    ];

    /** @return list<string> */
    public static function canonical(): array
    {
        return [
            self::INT,
            self::FLOAT,
            self::STRING,
            self::BOOL,
            self::ARRAY,
            self::OBJECT,
            self::CALLABLE,
            self::MIXED,
            self::NULL,
            self::NEVER,
            self::VOID,
        ];
    }

    /** @return list<string> */
    public static function expanded(): array
    {
        return [
            ...self::canonical(),
            ...array_keys(self::ALIASES),
        ];
    }

    public static function normalize(string $type): string
    {
        if (! str_contains($type, '&') && str_contains($type, '|')) {
            return self::normalizeNullableUnion($type);
        }

        return self::normalizeSingleType($type);
    }

    private static function normalizeSingleType(string $type): string
    {
        if (class_exists($type) || interface_exists($type)) {
            return $type;
        }

        if (! in_array($type, self::expanded(), true)) {
            throw new NotAType($type);
        }

        return self::ALIASES[$type] ?? $type;
    }

    /**
     * Only `T|null` unions are supported; `mixed`, `void` and `never` cannot be
     * union members.
     */
    private static function normalizeNullableUnion(string $type): string
    {
        $members = array_values(array_unique(array_map(
            static fn (string $member): string => self::normalizeSingleType($member),
            explode('|', $type),
        )));

        if (count($members) === 1) {
            return $members[0];
        }

        if (count($members) === 2 && in_array(self::NULL, $members, true)) {
            $other = $members[0] === self::NULL ? $members[1] : $members[0];

            if ($other === self::MIXED || $other === self::VOID || $other === self::NEVER) {
                throw new NotAType($type);
            }

            return $other . '|' . self::NULL;
        }

        throw new NotAType($type);
    }

    public static function union(string $type, string ...$types): string
    {
        $all = array_values(array_unique(array_map(
            static fn (string $item): string => self::normalize($item),
            array_merge([$type], $types),
        )));

        if (in_array(self::VOID, $all, true)) {
            throw new InvalidArgumentException('void is not allowed in union types.');
        }

        if (in_array(self::MIXED, $all, true)) {
            return self::MIXED;
        }

        $all = array_values(array_filter(
            $all,
            static fn (string $item): bool => $item !== self::NEVER,
        ));

        return implode('|', $all);
    }

    public static function intersection(string $type, string ...$types): string
    {
        $all = array_values(array_unique(array_map(
            static fn (string $item): string => self::normalize($item),
            array_merge([$type], $types),
        )));

        if (in_array(self::NEVER, $all, true)) {
            return self::NEVER;
        }

        $forbidden = array_values(array_filter(
            self::canonical(),
            static fn (string $item): bool => $item !== self::NEVER,
        ));

        foreach ($all as $item) {
            if (in_array($item, $forbidden, true)) {
                throw new InvalidArgumentException('"' . $item . '" is not allowed in intersection types');
            }
        }

        return implode('&', $all);
    }

    public static function toLiteral(mixed $value): string
    {
        if ($value === true) {
            return 'true';
        }

        if ($value === false) {
            return 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_callable($value) || $value instanceof Closure) {
            return self::CALLABLE;
        }

        if (is_object($value)) {
            return '"' . $value::class . '"';
        }

        if (is_string($value)) {
            return '"' . $value . '"';
        }

        return (string) $value;
    }

    public static function toString(mixed $value): string
    {
        $type = gettype($value);

        if ($type === self::OBJECT && $value instanceof Closure) {
            return self::CALLABLE;
        }

        return self::normalize($type);
    }

    public static function of(mixed $value): string
    {
        return is_object($value) ?
            $value::class : self::normalize(gettype($value));
    }

    public static function ofConstant(string $constant): string
    {
        if (! defined($constant)) {
            throw new NoSuchConstant($constant);
        }

        return self::of(constant($constant));
    }

    public static function ofClassConstant(string $class, string $name): string
    {
        $constant = (new ReflectionClass($class))
            ->getReflectionConstant($name);

        if ($constant === false) {
            throw new NoSuchClassConstant($class, $name);
        }

        return self::of($constant->getValue());
    }

    /** @param class-string $class */
    public static function ofProperty(string $class, string $property): string
    {
        return self::fromReflectionType(
            (new ReflectionClass($class))
                ->getProperty($property)
                ->getType(),
        );
    }

    /** @param class-string $class */
    public static function ofMethod(string $class, string $method): string
    {
        return self::fromReflectionType(
            (new ReflectionClass($class))
                ->getMethod($method)
                ->getReturnType(),
        );
    }

    /** @param class-string $class */
    public static function ofMethodParameter(string $class, string $method, string $parameter): string
    {
        $params = (new ReflectionClass($class))
            ->getMethod($method)
            ->getParameters();

        foreach ($params as $param) {
            if ($param->getName() !== $parameter) {
                continue;
            }

            return self::fromReflectionType($param->getType());
        }

        throw new NoSuchParameter($parameter);
    }

    public static function ofFunction(string|callable $function): string
    {
        return self::fromReflectionType(
            (new ReflectionFunction(Closure::fromCallable($function)))
                ->getReturnType(),
        );
    }

    public static function ofFunctionParameter(string|callable $function, string $parameter): string
    {
        $params = (new ReflectionFunction(Closure::fromCallable($function)))
            ->getParameters();

        foreach ($params as $param) {
            if ($param->getName() !== $parameter) {
                continue;
            }

            return self::fromReflectionType($param->getType());
        }

        throw new NoSuchParameter($parameter);
    }

    public static function fromReflectionType(ReflectionType|null $type): string
    {
        if ($type === null) {
            return self::MIXED;
        }

        if ($type instanceof ReflectionUnionType) {
            return self::fromReflectionUnionType($type);
        }

        if ($type instanceof ReflectionIntersectionType) {
            return self::fromReflectionIntersectionType($type);
        }

        if ($type instanceof ReflectionNamedType) {
            return self::fromReflectionNamedType($type);
        }

        throw new UnsupportedReflectionType($type::class);
    }

    public static function fromReflectionUnionType(ReflectionUnionType $type): string
    {
        $types = [];
        foreach ($type->getTypes() as $reflector) {
            $types[] = self::fromReflectionType($reflector);
        }

        return implode('|', $types);
    }

    public static function fromReflectionIntersectionType(ReflectionIntersectionType $type): string
    {
        $types = [];
        foreach ($type->getTypes() as $reflector) {
            $types[] = self::fromReflectionType($reflector);
        }

        return implode('&', $types);
    }

    public static function fromReflectionNamedType(ReflectionNamedType $type): string
    {
        $name = $type->getName();

        if ($type->allowsNull() && $name !== self::MIXED && $name !== self::NULL) {
            return $name . '|' . self::NULL;
        }

        return $name;
    }
}
