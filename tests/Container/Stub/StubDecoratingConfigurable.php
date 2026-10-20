<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Stub;

final class StubDecoratingConfigurable implements StubDecorating
{
    public function __construct(public readonly StubDecorating $inner, public readonly string $suffix)
    {
    }

    public function label(): string
    {
        return $this->inner->label() . $this->suffix;
    }
}
