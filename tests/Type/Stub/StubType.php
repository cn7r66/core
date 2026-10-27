<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Type\Stub;

use Override;
use Vivarium\Type\Type;

final class StubType implements Type
{
    #[Override]
    public function accepts(Type $type): bool
    {
        return false;
    }

    #[Override]
    public function acceptsVar(mixed $var): bool
    {
        return false;
    }

    #[Override]
    public function __toString(): string
    {
        return 'Stub';
    }

    #[Override]
    public function equals(object $object): bool
    {
        return $object instanceof self;
    }

    #[Override]
    public function hash(): string
    {
        return 'Stub';
    }
}
