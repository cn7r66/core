<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Type;

use Vivarium\Equality\Equality;

/**
 * A PHP type, as a declaration would write it.
 *
 * Two types are equal when their canonical strings are equal; hash() derives from the same string.
 */
interface Type extends Equality
{
    /**
     * Whether a value of type $type can be passed where this type is expected, under strict_types.
     */
    public function accepts(Type $type): bool;

    /**
     * Whether $var can be passed where this type is expected, under strict_types.
     */
    public function acceptsVar(mixed $var): bool;

    /**
     * The canonical form: lowercase keywords, declared class names, members in canonical order.
     */
    public function __toString(): string;
}
