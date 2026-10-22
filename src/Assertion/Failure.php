<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Assertion;

final class Failure
{
    public function __construct(
        private string $template,
        private mixed ...$parameters,
    ) {
    }

    public function withTemplate(string $template): Failure
    {
        $failure           = clone $this;
        $failure->template = $template;

        return $failure;
    }
}
