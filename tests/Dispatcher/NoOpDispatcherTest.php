<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Dispatcher;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Dispatcher\Event;
use Vivarium\Dispatcher\NoOpDispatcher;

#[CoversClass(NoOpDispatcher::class)]
final class NoOpDispatcherTest extends TestCase
{
    public function testDispatch(): void
    {
        $event       = $this->createStub(Event::class);
        $eventReturn = (new NoOpDispatcher())->dispatch($event);

        static::assertSame($event, $eventReturn);
    }
}
