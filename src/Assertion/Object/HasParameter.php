<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Assertion\Object;

use ReflectionClass;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\String\IsEmpty;
use Vivarium\Assertion\Type\IsClassOrInterface;
use Vivarium\Assertion\Var\IsString;
use Vivarium\Type\Type;

use function sprintf;

/** @template-implements Assertion<string> */
final class HasParameter implements Assertion
{
    public function __construct(private string $class, private string $method)
    {
        (new HasMethod($method))
            ->assert($class);
    }

    /** @psalm-assert string $value */
    public function assert(mixed $value, string $message = ''): void
    {
        if (! $this($value)) {
            $message = sprintf(
                ! (new IsEmpty())($message) ?
                    $message : 'Expected method %s to have a parameter named %2$s.',
                Type::toLiteral($this->method),
                Type::toLiteral($value),
            );

            throw new AssertionFailed($message);
        }
    }

    /** @psalm-assert-if-true string $value */
    public function __invoke(mixed $value): bool
    {
        (new IsString())
            ->assert($value);

            (new IsClassOrInterface())
            ->assert($this->class);

        $parameters = (new ReflectionClass($this->class))
            ->getMethod($this->method)
            ->getParameters();

        foreach ($parameters as $parameter) {
            if ($parameter->getName() === $value) {
                return true;
            }
        }

        return false;
    }
}
