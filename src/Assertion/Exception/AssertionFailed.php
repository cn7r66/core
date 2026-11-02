<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion\Exception;

use InvalidArgumentException;
use Override;
use Vivarium\Failure\Failed;
use Vivarium\Failure\Failure;

final class AssertionFailed extends InvalidArgumentException implements Failed
{
    public function __construct(private Failure $failure)
    {
        parent::__construct($failure->format());
    }

    #[Override]
    public function getFailure(): Failure
    {
        return $this->failure;
    }
}
