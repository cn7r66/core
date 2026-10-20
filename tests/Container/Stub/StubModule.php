<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Stub;

use Vivarium\Container\Binder;
use Vivarium\Container\Module;

final class StubModule implements Module
{
    public function configure(Binder $binder): Binder
    {
        return $binder
            ->bind(Stub::class)->to(StubClass::class)->service()
            ->decorate(Stub::class)->withDecorator(new StubDecorator())->withDefaultPriority();
    }
}
