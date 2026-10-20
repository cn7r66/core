<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Container;

interface Module
{
    /**
     * @param T $binder
     *
     * @return T
     *
     * @template T of Binder
     */
    public function configure(Binder $binder): Binder;
}
