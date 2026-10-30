<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Equality\Stub;

use Override;
use Vivarium\Equality\Equality;

final class EqualityStub implements Equality
{
    #[Override]
    public function equals(object $object): bool
    {
        return $object instanceof EqualityStub;
    }

    #[Override]
    public function hash(): string
    {
        return 'equality-stub';
    }
}
