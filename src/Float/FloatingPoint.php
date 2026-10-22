<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Float;

interface FloatingPoint
{
    public const float EPSILON    = 2.2204460492503E-16;
    public const float FLOAT_MIN  = 1.1754943508223E-38;
    public const float FLOAT_MAX  = 3.4028234663853E+38;
    public const float DOUBLE_MIN = 2.2250738585072E-308;
    public const float DOUBLE_MAX = 1.7976931348623E+308;
}
