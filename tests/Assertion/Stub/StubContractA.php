<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Stub;

interface StubContractA
{
    public const int CONTRACT = 1;

    public function described(): void;
}
