<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container;

use Vivarium\Collection\Set\Set;

interface Provider
{
    public function provide(Container $container): mixed;

    public function getTarget(): string;

    /** @return Set<Capability> */
    public function getCapabilities(): Set;
}
