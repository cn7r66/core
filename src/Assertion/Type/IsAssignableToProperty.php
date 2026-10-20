<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Type;

use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Object\HasProperty;
use Vivarium\Assertion\String\IsEmpty;
use Vivarium\Type\Type;

use function sprintf;

/**
 * @template T
 * @template-implements Assertion<class-string<T>>
 */
final class IsAssignableToProperty implements Assertion
{
    private string $type;

    /** @param class-string<T> $class */
    public function __construct(private string $class, private string $property)
    {
        (new IsClass())
            ->assert($class);

        (new HasProperty($property))
            ->assert($class);

        $this->type = Type::ofProperty($class, $property);
    }

    /** @psalm-assert class-string<T> $value */
    public function assert(mixed $value, string $message = ''): void
    {
        if (! $this($value)) {
            $message = sprintf(
                ! (new IsEmpty())($message) ?
                    $message : 'Expected type %s to be assignable to property %2$s of class %3$s.',
                Type::toLiteral($value),
                $this->property,
                Type::toLiteral($this->class),
            );

            throw new AssertionFailed($message);
        }
    }

    /**
     * @psalm-assert class-string $value
     * @psalm-assert-if-true class-string<T> $value
     */
    public function __invoke(mixed $value): bool
    {
        (new IsType())
            ->assert($value);

        return (new IsAssignableTo($this->type))($value);
    }
}
