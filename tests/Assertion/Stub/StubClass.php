<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Stub;

class StubClass implements Stub
{
    public const INT_CONSTANT    = 42;
    public const STRING_CONSTANT = 'hello';

    public int $prop = 42;

    public Stub $stubProp;

    public StubClass $classProp;

    protected int $protectedProp = 42;

    private int $privateProp = 42;

    public function __toString(): string
    {
        return 'StubClass';
    }

    public function publicMethod(): void
    {
    }

    protected function protectedMethod(): void
    {
    }

    private function privateMethod(): void
    {
    }
}
