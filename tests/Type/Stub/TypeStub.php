<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Type\Stub;

// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
// phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
class TypeStub implements StubInterfaceA, StubInterfaceB
{
    public int $intProp = 0;

    public string $stringProp = '';

    public string|null $nullableProp = null;

    public mixed $mixedProp = null;

    /** @var mixed */
    public $untypedProp = null;

    public function returnsInt(): int
    {
        return 0;
    }

    public function returnsString(): string
    {
        return '';
    }

    public function returnsNullableString(): string|null
    {
        return null;
    }

    public function returnsUnion(): int|string
    {
        return 0;
    }

    public function returnsIntersection(): StubInterfaceA&StubInterfaceB
    {
        return $this;
    }

    public function returnsIntOrNull(): int|null
    {
        return null;
    }

    public function returnsInterface(): StubInterfaceA
    {
        return new TypeStub();
    }

    public function returnsClass(): TypeStub
    {
        return new TypeStub();
    }

    public function returnsIntOrStringOrNull(): int|string|null
    {
        return null;
    }

    public function returnsVoid(): void
    {
    }

    public function returnsMixed(): mixed
    {
        return null;
    }

    /** @return mixed */
    public function returnsNoType()
    {
        return null;
    }

    public function takesInt(int $value): void
    {
    }

    public function takesNullable(string|null $value): void
    {
    }

    public function takesUnion(int|string $value): void
    {
    }

    public function takesUntyped(mixed $value): void
    {
    }

    public static function returnsFloat(): float
    {
        return 0.0;
    }

    public static function takesFloat(float $value): void
    {
    }

    public function __invoke(): int
    {
        return 0;
    }
}
