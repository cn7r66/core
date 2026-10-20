<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Type\Exception;

use InvalidArgumentException;

use function sprintf;

final class NoSuchClassConstant extends InvalidArgumentException
{
    public function __construct(string $class, string $constant)
    {
        parent::__construct(
            sprintf('No constant "$%s" found in class "$%s".', $constant, $class),
        );
    }
}
