<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Collection\Exception;

use Override;
use UnderflowException;
use Vivarium\Failure\Failure;

final class CollectionIsEmpty extends UnderflowException implements CollectionOperationFailed
{
    private Failure $failure;

    /** @param string $operation the verb that needed an element, written into the template */
    public function __construct(string $operation, object $collection)
    {
        $this->failure = new Failure(
            'Expected an element to ' . $operation . '. Got {value} with no elements.',
            $collection,
        );

        parent::__construct($this->failure->format());
    }

    #[Override]
    public function getFailure(): Failure
    {
        return $this->failure;
    }
}
