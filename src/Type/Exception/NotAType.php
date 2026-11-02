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

final class NotAType extends InvalidArgumentException implements InvalidType
{
    private Failure $failure;

    /** @param string $reason a template fragment, filled from $parameters */
    public function __construct(mixed $value, string $reason, mixed ...$parameters)
    {
        $this->failure = new Failure('Expected a valid type: ' . $reason . '. Got {value}.', $value, ...$parameters);

        parent::__construct($this->failure->format());
    }

    #[Override]
    public function getFailure(): Failure
    {
        return $this->failure;
    }
}
