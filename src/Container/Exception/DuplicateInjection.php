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

final class DuplicateInjection extends RuntimeException implements ContainerException
{
    public function __construct(private Binding $binding, private string $slot)
    {
        parent::__construct(
            sprintf(
                'Injection for "%s" is already registered on %s. Register it on a more specific type to override.',
                $slot,
                (string) $binding,
            ),
        );
    }

    public function getBinding(): Binding
    {
        return $this->binding;
    }

    public function getSlot(): string
    {
        return $this->slot;
    }
}
