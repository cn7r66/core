<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Core\Stub;

use Vivarium\Container\Binder;
use Vivarium\Container\Module;
use Vivarium\Dispatcher\EventDispatcher;

final class StubModule implements Module
{
    public function configure(Binder $binder): Binder
    {
        return $binder
            ->bind(EventDispatcher::class)->to(StubEventDispatcher::class)->transient();
    }
}
