<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Stub;

final class StubClass extends StubBase
{
    public const int INT_CONSTANT       = 42;
    public const string STRING_CONSTANT = 'hello';
    public const float FLOAT_CONSTANT   = 3.14;
    public const true BOOL_CONSTANT     = true;
    public const null NULL_CONSTANT     = null;
    public const array ARRAY_CONSTANT   = ['foo', 'bar'];

    public string $property;

    public mixed $untypedProperty;

    public function __construct(StubService $service)
    {
        $this->service = $service;
    }

    public function setService(StubService $service): void
    {
        $this->service = $service;
    }

    public function withService(StubService $service): static
    {
        $clone          = clone $this;
        $clone->service = $service;

        return $clone;
    }
}
