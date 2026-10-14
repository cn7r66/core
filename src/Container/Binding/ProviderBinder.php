<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Container\Binding;

use ReflectionFunction;
use Vivarium\Assertion\Boolean\IsTrue;
use Vivarium\Assertion\Conditional\IsNotNull;
use Vivarium\Assertion\Object\IsInstanceOf;
use Vivarium\Assertion\Type\IsAssignableTo;
use Vivarium\Container\Binding;
use Vivarium\Container\Provider;
use Vivarium\Container\Provider\ClassConstant;
use Vivarium\Container\Provider\Constant;
use Vivarium\Container\Provider\Constructor;
use Vivarium\Container\Provider\ContainerCall;
use Vivarium\Container\Provider\Enum;
use Vivarium\Container\Provider\Factory;
use Vivarium\Container\Provider\Instance;
use Vivarium\Container\Provider\StaticFactory;

use function defined;
use function enum_exists;

/** @template T */
final class ProviderBinder
{
    /** @var callable (Binding, Provider):T */
    private $create;

    /** @param callable(Binding, Provider): T $create */
    public function __construct(private Binding $source, callable $create)
    {
        (new IsNotNull())
            ->assert(
                (new ReflectionFunction($create))->getReturnType(),
                '"Missing type hint on callback function."',
            );

        $this->create = $create;
    }

    /** @return T */
    public function to(
        string $id,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ) {
        (new IsAssignableTo($this->source->getType()))
            ->assert($id);

        return $this->toProvider(
            new ContainerCall(
                new Binding($id, $tag, $context),
            ),
        );
    }

    /**
     * @param callable(Constructor): Constructor|null $configure
     *
     * @return T
     */
    public function toConstructor(callable|null $configure = null)
    {
        $class = $this->source->getType();

        if ($configure === null) {
            $configure = static fn (Constructor $constructor): Constructor => $constructor;
        }

        return $this->toProvider($configure(new Constructor($class)));
    }

    /** @return MethodBinder<T> */
    public function toFactory(
        string $class,
        string $tag = Binding::DEFAULT,
        string $context = Binding::GLOBAL,
    ): MethodBinder {
        $binding = new Binding($class, $tag, $context);

        return new MethodBinder(function (string $method, callable $configure) use ($binding) {
            return $this->toProvider(
                $configure(new Factory($binding, $method)),
            );
        });
    }

    /** @return MethodBinder<T> */
    public function toStaticFactory(
        string $class,
    ): MethodBinder {
        return new MethodBinder(function (string $method, callable $configure) use ($class) {
            return $this->toProvider(
                $configure(new StaticFactory($class, $method)),
            );
        });
    }

    /** @return T */
    public function toInstance(mixed $instance)
    {
        (new IsNotNull())
            ->assert($instance);

        (new IsInstanceOf($this->source->getType()))
            ->assert($instance);

        return $this->toProvider(
            new Instance($instance),
        );
    }

    /** @return T */
    public function toConstant(string $constant)
    {
        (new IsTrue())
            ->assert(defined($constant));

        return $this->toProvider(
            new Constant($constant),
        );
    }

    /** @return T */
    public function toEnum(string $enum, string $case)
    {
        (new IsTrue())
            ->assert(enum_exists($enum));

        return $this->toProvider(
            new Enum($enum, $case),
        );
    }

    /** @return T */
    public function toClassConstant(string $class, string $constant)
    {
        return $this->toProvider(
            new ClassConstant($class, $constant),
        );
    }

    /** @return T */
    public function toProvider(Provider $provider): mixed
    {
        return ($this->create)(
            $this->source,
            $provider,
        );
    }
}
