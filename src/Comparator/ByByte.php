<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Comparator;

use Override;
use Vivarium\Assertion\Var\IsString;

use function strcmp;

/**
 * Byte order, as strcmp(): not locale collation, so 'B' comes before 'a'. For UTF-8 strings it
 * is code point order.
 *
 * @template-implements Comparator<string>
 */
final class ByByte implements Comparator
{
    private IsString $isString;

    public function __construct()
    {
        $this->isString = new IsString();
    }

    /**
     * @param string $first
     * @param string $second
     */
    #[Override]
    public function compare($first, $second): int
    {
        $this->isString->assert($first);
        $this->isString->assert($second);

        return strcmp($first, $second);
    }
}
