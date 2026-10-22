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
use Vivarium\Dispatcher\StoppableEvent;
use Vivarium\Test\Dispatcher\Stub\StubStoppableEvent;

#[CoversClass(StoppableEvent::class)]
final class StoppableEventTest extends TestCase
{
    public function testPropagation(): void
    {
        $event = new StubStoppableEvent();

        static::assertFalse($event->isPropagationStopped());

        $event->stopPropagation();

        static::assertTrue($event->isPropagationStopped());
    }
}
