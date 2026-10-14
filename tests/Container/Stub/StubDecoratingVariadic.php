<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Stub;

final class StubDecoratingVariadic implements StubDecorating
{
    /** @var array<int, StubDecorating> */
    public readonly array $inners;

    public function __construct(StubDecorating ...$inners)
    {
        $this->inners = $inners;
    }

    public function label(): string
    {
        return '';
    }
}
