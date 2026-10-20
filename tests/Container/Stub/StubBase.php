<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Stub;

abstract class StubBase implements Stub
{
    public StubService|null $service = null;

    public function getService(): StubService|null
    {
        return $this->service;
    }
}
