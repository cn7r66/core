<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Type\Exception;

use InvalidArgumentException;
use Override;
use Vivarium\Failure\Failure;
use Vivarium\Type\Intersection;
use Vivarium\Type\Type;

final class NotAnIntersection extends InvalidArgumentException implements InvalidType
{
    private Failure $failure;

    /**
     * @param list<Type> $members
     * @param string     $reason  a template fragment, filled from $parameters
     */
    public function __construct(array $members, string $reason, mixed ...$parameters)
    {
        $this->failure = new Failure(
            'Expected a valid intersection: ' . $reason . '. Got {value}.',
            Intersection::format(...$members),
            ...$parameters,
        );

        parent::__construct($this->failure->format());
    }

    #[Override]
    public function getFailure(): Failure
    {
        return $this->failure;
    }
}
