<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container;

use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use Vivarium\Assertion\Boolean\IsTrue;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Numeric\IsInHalfOpenRightRange;
use Vivarium\Assertion\Object\HasMethod;
use Vivarium\Assertion\Type\IsClass;
use Vivarium\Assertion\Var\IsFunction;
use Vivarium\Collection\Map\HashMap;
use Vivarium\Collection\Map\Map;
use Vivarium\Container\Exception\CannotBeOmitted;
use Vivarium\Container\Exception\CannotBeProjected;
use Vivarium\Container\Exception\CannotBeReplaced;
use Vivarium\Type\Type;

use function sprintf;

/**
 * A callable signature (constructor, method or function) reduced to its
 * ordered parameter descriptors.
 *
 * Parameters are addressed by zero-based position; names are looked up to a
 * position.
 */
final class Signature
{
    private Map $lookup;

    private function __construct(
        private Map $parameters = new HashMap(),
    ) {
        $lookup = new HashMap();

        foreach ($this->parameters as $position => $parameter) {
            $lookup = $lookup->put($parameter->getName(), $position);
        }

        $this->lookup = $lookup;
    }

    /**
     * Builds the signature of $class's constructor.
     *
     * When $class has no constructor at all, the returned signature has zero
     * parameters rather than raising an error.
     */
    public static function ofConstructor(string $class): self
    {
        (new IsClass())
            ->assert($class);

        $constructor = (new ReflectionClass($class))
            ->getConstructor();

        if ($constructor === null) {
            return new Signature();
        }

        return self::fromReflector(
            $constructor,
            $class,
        );
    }

    /**
     * Builds the signature of the named method on $class.
     *
     * @throws AssertionFailed If $class has no method named $method.
     */
    public static function ofMethod(string $class, string $method): self
    {
        (new HasMethod($method))
            ->assert($class);

        return self::fromReflector(
            new ReflectionMethod($class, $method),
            $class,
        );
    }

    /**
     * Builds the signature of $fn.
     *
     * Only a `Closure` or the name of an existing named function is accepted;
     * method-array and invokable-object callables are rejected, since
     * `ReflectionFunction` cannot reflect them. The declaring context is
     * always `Binding::GLOBAL`.
     */
    public static function ofFunction(callable $fn): self
    {
        (new IsFunction())
            ->assert($fn);

        return self::fromReflector(
            new ReflectionFunction($fn),
            Binding::GLOBAL,
        );
    }

    private static function fromReflector(
        ReflectionFunctionAbstract $reflector,
        string $context,
    ): self {
        $parameters = new HashMap();

        foreach ($reflector->getParameters() as $position => $parameterReflector) {
            $parameters = $parameters->put(
                $position,
                self::fromParameter($parameterReflector, $context),
            );
        }

        return new Signature($parameters);
    }

    private static function fromParameter(ReflectionParameter $parameter, string $context): Parameter
    {
        $type = Type::normalize(
            Type::fromReflectionType(
                $parameter->getType(),
            ),
        );

        if ($parameter->isVariadic()) {
            return new Parameter\Variadic(
                $parameter->getName(),
                $type,
                $context,
            );
        }

        if ($parameter->isDefaultValueAvailable()) {
            return new Parameter\Optional(
                $parameter->getName(),
                $type,
                $context,
                $parameter,
            );
        }

        return new Parameter\Required(
            $parameter->getName(),
            $type,
            $context,
        );
    }

    public function count(): int
    {
        return $this->parameters->count();
    }

    /**
     * Returns the parameter descriptor at $position.
     *
     * Position 0 is the first declared parameter.
     */
    public function getParameter(int $position): Parameter
    {
        (new IsInHalfOpenRightRange(0, $this->count()))
            ->assert($position);

        return $this->parameters->get($position);
    }

    /**
     * Returns the position of the parameter named $name.
     *
     * @throws AssertionFailed If no parameter is named $name.
     */
    public function positionOf(string $name): int
    {
        (new IsTrue())
            ->assert(
                $this->lookup->containsKey($name),
                sprintf('Signature has no parameter named %s.', $name),
            );

        return $this->lookup->get($name);
    }

    /** Whether an explicit argument has been bound at $position. */
    public function hasArgument(int $position): bool
    {
        (new IsInHalfOpenRightRange(0, $this->count()))
            ->assert($position);

        return $this->parameters->get($position)->isExplicit();
    }

    /**
     * Returns a new signature with $provider explicitly bound at $position.
     *
     * Binding a position that was already bound overwrites the previous
     * provider. Variadic positions are bindable: the bound provider for a
     * variadic slot promises the whole collected array the slot receives.
     */
    public function withArgument(int $position, Provider $provider): self
    {
        (new IsInHalfOpenRightRange(0, $this->count()))
            ->assert(
                $position,
                'No argument at position %s.',
            );

        $signature             = clone $this;
        $signature->parameters = $signature->parameters->put(
            $position,
            $signature->parameters
                ->get($position)
                ->withArgument($provider),
        );

        return $signature;
    }

    /**
     * Returns the effective argument providers for this signature, keyed by
     * position.
     *
     * Every position is present; an unbound variadic yields its default
     * provider.
     *
     * @return array<int, Provider>
     */
    public function getArguments(): array
    {
        $arguments = [];

        foreach ($this->parameters as $position => $parameter) {
            $arguments[$position] = $parameter->getArgument();
        }

        return $arguments;
    }

    /**
     * Projects this signature onto $target, the signature that overrides it.
     *
     * Parameters are aligned by position; names are ignored. $target's own
     * explicit bindings win; this signature only fills positions $target
     * leaves unbound.
     *
     * @throws CannotBeProjected If $target has fewer positions than this one.
     * @throws CannotBeReplaced If an aligned parameter of $target is not a
     *                          legal PHP override (kind or type).
     * @throws CannotBeOmitted If $target has extra positions that cannot be
     *                         omitted.
     */
    public function project(Signature $target): Signature
    {
        if ($target->count() < $this->count()) {
            throw new CannotBeProjected(sprintf(
                'Target signature has %d positions but the base signature requires at least %d.',
                $target->count(),
                $this->count(),
            ));
        }

        for ($position = $this->count(); $position < $target->count(); $position++) {
            if (! $target->getParameter($position)->canBeOmitted()) {
                throw new CannotBeOmitted(sprintf(
                    'Target parameter $%s at position %d cannot be omitted.',
                    $target->getParameter($position)->getName(),
                    $position,
                ));
            }
        }

        return new Signature(
            $this->projectParameters($target),
        );
    }

    /** @return Map<int, Parameter> */
    private function projectParameters(Signature $target): Map
    {
        $parameters = $target->parameters;
        for ($position = 0; $position < $this->count(); $position++) {
            $sourceParameter = $this->getParameter($position);
            $targetParameter = $target->getParameter($position);

            if (! $targetParameter->canReplace($sourceParameter)) {
                throw new CannotBeReplaced(sprintf(
                    'Parameter $%s cannot replace parameter $%s at position %d.',
                    $targetParameter->getName(),
                    $sourceParameter->getName(),
                    $position,
                ));
            }

            $parameters = $parameters->put(
                $position,
                $this->projectParameter(
                    $sourceParameter,
                    $targetParameter,
                ),
            );
        }

        return $parameters;
    }

    private function projectParameter(Parameter $source, Parameter $target): Parameter
    {
        if ($target->isExplicit() || ! $source->isExplicit()) {
            return $target;
        }

        return $target->withArgument(
            $source->getArgument(),
        );
    }
}
