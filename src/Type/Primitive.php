<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Type;

use Override;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;
use Vivarium\Type\Exception\NotAPrimitive;

use function array_all;
use function array_any;
use function in_array;
use function is_array;
use function is_bool;
use function is_callable;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function strtolower;

final class Primitive implements Type
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
    public const string TRUE     = 'true';
    public const string FALSE    = 'false';

    public const array KEYWORDS = [
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
        self::TRUE,
        self::FALSE,
    ];

    private const array WIDENINGS = [
        self::FLOAT => [self::INT],
        self::BOOL  => [self::TRUE, self::FALSE],
    ];

    private string $type;

    public function __construct(string $type)
    {
        $keyword = self::format($type);
        if (! in_array($keyword, self::KEYWORDS, true)) {
            throw new NotAPrimitive($type);
        }

        $this->type = $keyword;
    }

    /** The canonical spelling of a keyword: lowercase. */
    public static function format(string $type): string
    {
        return strtolower($type);
    }

    #[Override]
    public function accepts(Type $type): bool
    {
        if ((string) $type === self::NEVER) {
            return true;
        }

        if ($this->type === self::MIXED) {
            return (string) $type !== self::VOID;
        }

        if ($type instanceof Union) {
            return array_all($type->getMembers(), fn (Type $member): bool => $this->accepts($member));
        }

        if ($type instanceof Intersection) {
            return array_any($type->getMembers(), fn (Type $member): bool => $this->accepts($member));
        }

        if ($type instanceof Clazz) {
            return $this->acceptsClass($type);
        }

        if ($this->type === (string) $type) {
            return true;
        }

        return in_array((string) $type, self::WIDENINGS[$this->type] ?? [], true);
    }

    #[Override]
    public function acceptsVar(mixed $var): bool
    {
        return match ($this->type) {
            self::MIXED => true,
            self::INT => is_int($var),
            self::FLOAT => is_float($var) || is_int($var),
            self::STRING => is_string($var),
            self::BOOL => is_bool($var),
            self::TRUE => $var === true,
            self::FALSE => $var === false,
            self::NULL => $var === null,
            self::ARRAY => is_array($var),
            self::OBJECT => is_object($var),
            self::CALLABLE => is_callable($var),
            default => false,
        };
    }

    #[Override]
    public function __toString(): string
    {
        return $this->type;
    }

    #[Override]
    public function equals(object $object): bool
    {
        if (! $object instanceof Primitive) {
            return false;
        }

        if ($object === $this) {
            return true;
        }

        return (new EqualsBuilder())
            ->append($this->type, $object->type)
            ->isEquals();
    }

    #[Override]
    public function hash(): string
    {
        return (new HashBuilder())
            ->append($this->type)
            ->getHashCode();
    }

    private function acceptsClass(Clazz $type): bool
    {
        if ($this->type === self::OBJECT) {
            return true;
        }

        return $this->type === self::CALLABLE && $type->getReflection()->hasMethod('__invoke');
    }
}
