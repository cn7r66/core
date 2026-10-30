<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Equality;

/**
 * equals() must be reflexive, symmetric and transitive, and two equal objects must return the
 * same hash(). An object that does not implement Equality is never equal to one that does.
 */
interface Equality
{
    public function equals(object $object): bool;

    public function hash(): string;
}
