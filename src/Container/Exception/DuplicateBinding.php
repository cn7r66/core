<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Container\Exception;

use RuntimeException;
use Vivarium\Container\Binding;

use function sprintf;

final class DuplicateBinding extends RuntimeException implements ContainerException
{
    public function __construct(private Binding $binding)
    {
        parent::__construct(
            sprintf('Binding %s is already registered. Use extend() to replace it.', (string) $binding),
        );
    }

    public function getBinding(): Binding
    {
        return $this->binding;
    }
}
