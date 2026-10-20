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

final class NoSuchConstant extends InvalidArgumentException
{
    public function __construct(string $constant)
    {
        parent::__construct(
            sprintf('No constant "$%s" found.', $constant),
        );
    }
}
