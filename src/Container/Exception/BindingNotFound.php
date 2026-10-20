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

final class BindingNotFound extends RuntimeException implements ContainerException
{
    public function __construct(private Binding $binding)
    {
        parent::__construct('No binding found for ' . $binding . '.');
    }

    public function getBinding(): Binding
    {
        return $this->binding;
    }
}
