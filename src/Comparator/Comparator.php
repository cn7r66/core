<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Comparator;

/**
 * Only the sign of compare() carries the answer: negative when the first value comes before the
 * second, positive when it comes after, zero when they are equivalent. The order must be
 * antisymmetric (swapping the values flips the sign) and transitive, so zero is an equivalence
 * a sort or a search can rely on.
 *
 * @template-contravariant T
 */
interface Comparator
{
    /**
     * @param T $first
     * @param T $second
     */
    public function compare($first, $second): int;
}
