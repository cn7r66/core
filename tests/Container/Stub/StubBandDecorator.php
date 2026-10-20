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

final class StubBandDecorator implements Decorator
{
    public function enhance(mixed $instance, Container $container): mixed
    {
        if ($instance instanceof StubBandSubject) {
            $instance->log[] = 'decoration';
        }

        return $instance;
    }

    public function accept(Provider $provider): bool
    {
        return true;
    }
}
