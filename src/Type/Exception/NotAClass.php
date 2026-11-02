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

use function trait_exists;

final class NotAClass extends InvalidArgumentException implements InvalidType
{
    private Failure $failure;

    public function __construct(string $class)
    {
        $this->failure = new Failure(
            trait_exists($class) ?
                'Expected a class, interface or enum, not a trait. Got {value}.' :
                'Expected an existing class, interface or enum. Got {value}.',
            $class,
        );

        parent::__construct($this->failure->format());
    }

    #[Override]
    public function getFailure(): Failure
    {
        return $this->failure;
    }
}
