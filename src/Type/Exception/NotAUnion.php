<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Type\Exception;

use InvalidArgumentException;
use Vivarium\Type\Type;
use Vivarium\Type\Union;

use function sprintf;

final class NotAUnion extends InvalidArgumentException implements InvalidType
{
    /** @param list<Type> $members */
    public function __construct(private array $members, private string $reason)
    {
        parent::__construct(sprintf(
            'Expected "%s" to be a valid union: %s.',
            Union::format(...$members),
            $reason,
        ));
    }

    /** @return list<Type> */
    public function getMembers(): array
    {
        return $this->members;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
