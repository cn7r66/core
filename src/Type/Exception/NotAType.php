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

final class NotAType extends InvalidArgumentException implements InvalidType
{
    public function __construct(private string $type, private string $reason)
    {
        parent::__construct(sprintf('Expected "%s" to be a type: %s.', $type, $reason));
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
