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

final class NotAPrimitive extends InvalidArgumentException implements InvalidType
{
    private Failure $failure;

    public function __construct(string $type)
    {
        $this->failure = new Failure('Expected a primitive type. Got {value}.', $type);

        parent::__construct($this->failure->format());
    }

    #[Override]
    public function getFailure(): Failure
    {
        return $this->failure;
    }
}
