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
use function trait_exists;

final class NotAClass extends InvalidArgumentException implements InvalidType
{
    public function __construct(private string $class)
    {
        parent::__construct(sprintf(
            trait_exists($class) ?
                'Expected "%s" to be a class, interface or enum. Got a trait.' :
                'Expected "%s" to be an existing class, interface or enum.',
            $class,
        ));
    }

    public function getClass(): string
    {
        return $this->class;
    }
}
