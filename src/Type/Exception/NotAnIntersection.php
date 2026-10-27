<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Type\Exception;

use InvalidArgumentException;
use Vivarium\Type\Intersection;
use Vivarium\Type\Type;

use function sprintf;

final class NotAnIntersection extends InvalidArgumentException implements InvalidType
{
    /** @param list<Type> $members */
    public function __construct(private array $members, private string $reason)
    {
        parent::__construct(sprintf(
            'Expected "%s" to be a valid intersection: %s.',
            Intersection::format(...$members),
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
