<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container;

interface Enhancement
{
    /**
     * @param T $instance
     *
     * @return T
     */
    public function enhance(mixed $instance, Container $container): mixed;

    public function accept(Provider $provider): bool;
}
