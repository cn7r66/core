<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Collector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Container\Collector\NoOpCollector;

#[CoversClass(NoOpCollector::class)]
final class NoOpCollectorTest extends TestCase
{
    public function testCollectRunsWithoutError(): void
    {
        $this->expectNotToPerformAssertions();

        (new NoOpCollector())->collect();
    }
}
