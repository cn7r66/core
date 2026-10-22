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
use Vivarium\Dispatcher\ListenerAndPriority;
use Vivarium\Dispatcher\ListenerAndPriorityComparator;
use Vivarium\Dispatcher\Priority;
use Vivarium\Test\Dispatcher\Stub\GenericEventListener;

use function usort;

#[CoversClass(ListenerAndPriorityComparator::class)]
final class ListenerAndPriorityComparatorTest extends TestCase
{
    public function testSorting(): void
    {
        $veryLow  = new ListenerAndPriority(new GenericEventListener(), Priority::VERY_LOW);
        $low      = new ListenerAndPriority(new GenericEventListener(), Priority::LOW);
        $normal   = new ListenerAndPriority(new GenericEventListener(), Priority::NORMAL);
        $high     = new ListenerAndPriority(new GenericEventListener(), Priority::HIGH);
        $veryHigh = new ListenerAndPriority(new GenericEventListener(), Priority::VERY_HIGH);

        /** @var ListenerAndPriority<Event>[] $listeners */
        $listeners = [$normal, $high, $veryLow, $low, $veryHigh];

        usort($listeners, new ListenerAndPriorityComparator());

        $expected = [$veryHigh, $high, $normal, $low, $veryLow];

        static::assertSame($expected, $listeners);
    }
}
