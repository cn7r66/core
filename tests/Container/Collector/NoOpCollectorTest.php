<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Container\Collector;

use PHPUnit\Framework\TestCase;
use Vivarium\Container\Collector\NoOpCollector;

/** @coversDefaultClass \Vivarium\Container\Collector\NoOpCollector */
final class NoOpCollectorTest extends TestCase
{
    /** @covers ::collect */
    public function testCollectRunsWithoutError(): void
    {
        $this->expectNotToPerformAssertions();

        (new NoOpCollector())->collect();
    }
}
