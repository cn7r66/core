<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Class;

use Override;
use Vivarium\Assertion\Assertion;
use Vivarium\Assertion\BaseAssertion;
use Vivarium\Assertion\Failure;

use function is_subclass_of;

/**
 * @template I of object
 * @template-extends BaseAssertion<class-string<I>, class-string>
 */
final class ImplementsInterface extends BaseAssertion
{
    /** @param class-string<I> $interface */
    public function __construct(private readonly string $interface)
    {
        (new IsInterface())
            ->assert($interface);
    }

    #[Override]
    protected function precondition(): Assertion
    {
        return new IsClassOrInterface();
    }

    #[Override]
    protected function condition(mixed $value): bool
    {
        return is_subclass_of($value, $this->interface, true);
    }

    #[Override]
    protected function failure(mixed $value): Failure
    {
        return new Failure(
            'Expected value to implement {interface}. Got {value}.',
            $value,
            interface: $this->interface,
        );
    }
}
