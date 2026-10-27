<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Type\Stub;

use Closure;
use Countable;
use Traversable;

class StubReflectionChild extends StubReflectionParent
{
    public const int TYPED = 1;

    /** @psalm-suppress MissingClassConstType */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ClassConstantTypeHint.MissingNativeTypeHint
    public const UNTYPED = 'x';

    public self|null $property = null;

    // phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint
    // phpcs:disable SlevomatCodingStandard.TypeHints.DNFTypeHintFormat

    /**
     * @param mixed                                      $untyped
     * @param iterable<mixed>                            $iterable
     * @param iterable<mixed>|null                       $nullableIterable
     * @param (Countable&Traversable<mixed, mixed>)|null $dnf
     */
    public function method(
        self $self,
        parent $parent,
        StubReflectionChild|null $named,
        mixed $mixed,
        null|int $nullInt,
        $untyped,
        iterable $iterable,
        ?iterable $nullableIterable,
        (Countable&Traversable)|null $dnf,
        int|string|null $scalars,
    ): static {
        return $this;
    }

    /** A closure whose `self` and `parent` resolve against the class it is bound to, this one by default. */
    public static function contextual(): Closure
    {
        return static function (parent|null $parent, self|int $union, self|null $nullable): void {
        };
    }
}
