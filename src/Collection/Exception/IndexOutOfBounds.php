<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Exception;

use OutOfBoundsException;
use Override;
use Vivarium\Failure\Failure;

final class IndexOutOfBounds extends OutOfBoundsException implements CollectionOperationFailed
{
    private Failure $failure;

    public function __construct(int $index, int $count)
    {
        $this->failure = new Failure('Expected an index in [0, {count}). Got {value}.', $index, count: $count);

        parent::__construct($this->failure->format());
    }

    #[Override]
    public function getFailure(): Failure
    {
        return $this->failure;
    }
}
