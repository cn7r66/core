<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Comparator;

interface Priority
{
    public const int VERY_VERY_LOW = -1000;

    public const int VERY_LOW = -100;

    public const int LOW = -50;

    public const int NORMAL = 0;

    public const int HIGH = 50;

    public const int VERY_HIGH = 100;

    public const int VERY_VERY_HIGH = 1000;
}
