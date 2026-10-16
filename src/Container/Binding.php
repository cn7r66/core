<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Container;

use Vivarium\Assertion\Comparison\IsSameOf;
use Vivarium\Assertion\Conditional\Either;
use Vivarium\Assertion\String\IsNotEmpty;
use Vivarium\Assertion\Type\IsClassOrInterface;
use Vivarium\Assertion\Type\IsNamespace;
use Vivarium\Assertion\Type\IsType;
use Vivarium\Check\CheckIfType;
use Vivarium\Collection\Sequence\ArraySequence;
use Vivarium\Collection\Sequence\Sequence;
use Vivarium\Container\Exception\CannotBeWidened;
use Vivarium\Equality\Equality;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Equality\HashBuilder;

use function array_merge;
use function class_implements;
use function get_parent_class;
use function is_a;
use function str_starts_with;
use function strrpos;
use function substr;

final class Binding implements Equality
{
    public const GLOBAL = '$GLOBAL';

    public const DEFAULT = '$DEFAULT';

    public function __construct(
        private string $type,
        private string $tag = self::DEFAULT,
        private string $context = self::GLOBAL,
    ) {
        (new IsType())
            ->assert($type);

        (new IsNotEmpty())
            ->assert($tag);

        (new Either(
            new IsSameOf(self::GLOBAL),
            new Either(
                new IsClassOrInterface(),
                new IsNamespace(),
            ),
        ))->assert($context, 'Expected string to be $GLOBAL, class, interface or namespace. Got %s.');
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    /** @return Sequence<Binding> */
    public function hierarchy(): Sequence
    {
        $types = [$this];
        if (CheckIfType::IsClassOrInterface($this->type)) {
            $types = array_merge(
                $types,
                $this->extends(),
                $this->interfaces(),
            );
        }

        return $this->expand($types);
    }

    public function widen(): Binding
    {
        if (! $this->couldBeWidened()) {
            throw new CannotBeWidened(
                'Binding ' . $this . ' cannot be widened further.',
            );
        }

        $pos = strrpos($this->context, '\\');

        $parent =  $pos !== false ?
            substr($this->context, 0, $pos) : self::GLOBAL;

        $binding          = clone $this;
        $binding->context = $parent;

        return $binding;
    }

    public function couldBeWidened(): bool
    {
        return $this->context !== self::GLOBAL;
    }

    public function isMoreSpecificThan(Binding $other): bool
    {
        if ($this->getTag() !== $other->getTag()) {
            return false;
        }

        if ($this->getType() !== $other->getType()) {
            return is_a($this->getType(), $other->getType(), true);
        }

        if ($this->getContext() === $other->getContext()) {
            return false;
        }

        if ($other->getContext() === self::GLOBAL) {
            return true;
        }

        return str_starts_with($this->getContext(), $other->getContext() . '\\');
    }

    public function equals(object $object): bool
    {
        if ($object === $this) {
            return true;
        }

        if (! $object instanceof Binding) {
            return false;
        }

        return (new EqualsBuilder())
            ->append($this->type, $object->getType())
            ->append($this->tag, $object->getTag())
            ->append($this->context, $object->getContext())
            ->isEquals();
    }

    public function hash(): string
    {
        return (new HashBuilder())
            ->append($this->type)
            ->append($this->tag)
            ->append($this->context)
            ->getHashCode();
    }

    /**
     * Renders the binding for error messages: `Type`, with `:tag` appended when the tag is
     * not DEFAULT and ` (in context)` appended when the context is not GLOBAL,
     * e.g. `Logger`, `Logger:audit`, `Logger:audit (in App\Orders)`.
     */
    public function __toString(): string
    {
        $text = $this->type;

        if ($this->tag !== self::DEFAULT) {
            $text .= ':' . $this->tag;
        }

        if ($this->context !== self::GLOBAL) {
            $text .= ' (in ' . $this->context . ')';
        }

        return $text;
    }

    /**
     * @param array<Binding> $bindings
     *
     * @return Sequence<Binding>
     */
    private function expand(array $bindings): Sequence
    {
        $hierarchy = [];
        foreach ($bindings as $binding) {
            $hierarchy[] = $binding;
            while ($binding->couldBeWidened()) {
                $binding     = $binding->widen();
                $hierarchy[] = $binding;
            }
        }

        return ArraySequence::fromArray($hierarchy);
    }

    /** @return array<Binding> */
    private function extends(): array
    {
        $extends = [];

        $extend = get_parent_class($this->type);
        while ($extend !== false) {
            $extends[] = new Binding(
                $extend,
                $this->tag,
                $this->getContext(),
            );

            $extend = get_parent_class($extend);
        }

        return $extends;
    }

    /** @return array<Binding> */
    private function interfaces(): array
    {
        $interfaces = [];
        foreach (class_implements($this->type) as $interface) {
            $interfaces[] = new Binding(
                $interface,
                $this->tag,
                $this->getContext(),
            );
        }

        return $interfaces;
    }
}
