<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Cache;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Container\Binding;
use Vivarium\Container\Cache\NoOpCache;

#[CoversClass(NoOpCache::class)]
final class NoOpCacheTest extends TestCase
{
    public function testLookupAlwaysReturnsFalse(): void
    {
        $cache   = new NoOpCache();
        $binding = new Binding('stdClass');

        static::assertFalse($cache->lookup($binding));
    }

    public function testRestoreThrowsLogicException(): void
    {
        $this->expectException(LogicException::class);

        (new NoOpCache())->restore(new Binding('stdClass'));
    }
}
