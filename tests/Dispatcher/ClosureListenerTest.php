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
use Vivarium\Dispatcher\ClosureListener;
use Vivarium\Dispatcher\Event;

#[CoversClass(ClosureListener::class)]
final class ClosureListenerTest extends TestCase
{
    public function testCallingClosure(): void
    {
        $mock = $this->createStub(Event::class);

        $listener = new ClosureListener(static function (Event $event) use ($mock): Event {
            static::assertSame($mock, $event);

            return $event;
        });

        $event = $listener->handle($mock);

        static::assertSame($mock, $event);
    }
}
