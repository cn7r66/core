<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container\Cache;

use LogicException;
use Vivarium\Container\Binding;
use Vivarium\Container\Cache;
use Vivarium\Container\Definition;

final class NoOpCache implements Cache
{
    public function lookup(Binding $binding): bool
    {
        return false;
    }

    public function restore(Binding $binding): Definition
    {
        throw new LogicException('NoOpCache has no entries to restore.');
    }
}
