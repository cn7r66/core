<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Binding;

use Closure;
use ReflectionFunction;
use Vivarium\Assertion\Boolean\IsFalse;
use Vivarium\Assertion\Type\IsAssignableTo;
use Vivarium\Container\Binding;
use Vivarium\Container\Decorator;
use Vivarium\Container\Decorator\Composition;
use Vivarium\Container\Decorator\Wrapper;
use Vivarium\Container\Parameter;
use Vivarium\Container\Parameter\Variadic;
use Vivarium\Container\Provider\Constructor;
use Vivarium\Container\Provider\Fun;
use Vivarium\Container\Signature;
use Vivarium\Type\Type;

use function is_int;
use function sprintf;

/** @template T */
final class DecoratorBinder
{
    /** @var callable(Decorator, int): T */
    private $create;

    public function __construct(private Binding $binding, callable $create, private int $defaultPriority)
    {
        $this->create = $create;
    }

    /** @return PriorityBinder<T> */
    public function withDecorator(Decorator $decorator): PriorityBinder
    {
        $create = $this->create;

        return new PriorityBinder(
            static fn (int $priority) => $create($decorator, $priority),
            $this->defaultPriority,
        );
    }

    /**
     * Wraps the instance in a new $class, passing it as the $slot constructor argument.
     *
     * @param callable(Constructor): Constructor|null $configure
     *
     * @return PriorityBinder<T>
     */
    public function with(string $class, string|int $slot, callable|null $configure = null): PriorityBinder
    {
        (new IsAssignableTo($this->binding->getType()))
            ->assert($class);

        $this->assertSlot(Signature::ofConstructor($class), $slot);

        $constructor = new Constructor($class);

        return $this->withDecorator(
            new Wrapper($configure === null ? $constructor : $configure($constructor), $slot),
        );
    }

    /**
     * Wraps the instance with the result of $fn, passing it as the $slot argument.
     *
     * @param callable(Fun): Fun|null $configure
     *
     * @return PriorityBinder<T>
     */
    public function withFunction(Closure|string $fn, string|int $slot, callable|null $configure = null): PriorityBinder
    {
        $this->assertSlot(Signature::ofFunction($fn), $slot);

        $returnType = (new ReflectionFunction($fn))->getReturnType();

        if ($returnType !== null) {
            (new IsAssignableTo($this->binding->getType()))
                ->assert(Type::fromReflectionType($returnType));
        }

        $function = new Fun($fn);

        return $this->withDecorator(
            new Wrapper($configure === null ? $function : $configure($function), $slot),
        );
    }

    /**
     * Replaces the callable instance with the composition of $fn after it, fed through the $slot argument.
     *
     * @param callable(Fun): Fun|null $configure
     *
     * @return PriorityBinder<T>
     */
    public function compose(Closure|string $fn, string|int $slot, callable|null $configure = null): PriorityBinder
    {
        (new IsAssignableTo('callable'))
            ->assert($this->binding->getType());

        $this->slotParameter(Signature::ofFunction($fn), $slot);

        $function = new Fun($fn);

        return $this->withDecorator(
            new Composition($configure === null ? $function : $configure($function), $slot),
        );
    }

    private function assertSlot(Signature $signature, string|int $slot): void
    {
        (new IsAssignableTo($this->slotParameter($signature, $slot)->getType()))
            ->assert($this->binding->getType());
    }

    private function slotParameter(Signature $signature, string|int $slot): Parameter
    {
        $parameter = $signature->getParameter(is_int($slot) ? $slot : $signature->positionOf($slot));

        (new IsFalse())
            ->assert(
                $parameter instanceof Variadic,
                sprintf('Parameter %s is variadic and cannot receive the decorated instance.', $parameter->getName()),
            );

        return $parameter;
    }
}
