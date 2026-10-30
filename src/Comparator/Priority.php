<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Comparator;

final class Priority
{
    public const int VERY_LOW = -100;

    public const int LOW = -50;

    public const int NORMAL = 0;

    public const int HIGH = 50;

    public const int VERY_HIGH = 100;

    private function __construct()
    {
    }
}
