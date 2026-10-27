<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Type\Exception;

use InvalidArgumentException;

use function sprintf;

final class NotAPrimitive extends InvalidArgumentException implements InvalidType
{
    public function __construct(private string $type)
    {
        parent::__construct(sprintf('Expected "%s" to be a primitive type.', $type));
    }

    public function getType(): string
    {
        return $this->type;
    }
}
