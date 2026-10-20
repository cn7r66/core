<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Stub;

use Vivarium\Container\Container;
use Vivarium\Container\Decorator;
use Vivarium\Container\Provider;

final class StubDecorator implements Decorator, Stub
{
    public Stub|null $inner = null;

    public function enhance(mixed $instance, Container $container): mixed
    {
        $clone        = clone $this;
        $clone->inner = $instance;

        return $clone;
    }

    public function accept(Provider $provider): bool
    {
        return true;
    }

    public function getService(): StubService|null
    {
        if ($this->inner === null) {
            return null;
        }

        return $this->inner->getService();
    }
}
