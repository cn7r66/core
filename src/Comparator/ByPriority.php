<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Comparator;

use Override;
use Vivarium\Assertion\Class\IsInstanceOf;

/**
 * Lowest priority first.
 *
 * @template T of Prioritized
 * @template-implements Comparator<T>
 */
final class ByPriority implements Comparator
{
    /** @var IsInstanceOf<Prioritized> */
    private IsInstanceOf $isPrioritized;

    public function __construct()
    {
        $this->isPrioritized = new IsInstanceOf(Prioritized::class);
    }

    /**
     * @param T $first
     * @param T $second
     */
    #[Override]
    public function compare($first, $second): int
    {
        $this->isPrioritized->assert($first);
        $this->isPrioritized->assert($second);

        return $first->getPriority() <=> $second->getPriority();
    }
}
