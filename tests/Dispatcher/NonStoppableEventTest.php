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
use Vivarium\Dispatcher\NonStoppableEvent;
use Vivarium\Test\Dispatcher\Stub\StubNonStoppableEvent;

#[CoversClass(NonStoppableEvent::class)]
final class NonStoppableEventTest extends TestCase
{
    public function testPropagation(): void
    {
        $event = new StubNonStoppableEvent();

        static::assertFalse($event->isPropagationStopped());
    }
}
