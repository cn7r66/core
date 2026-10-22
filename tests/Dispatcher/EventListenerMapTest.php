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
use Vivarium\Dispatcher\EventListenerMap;
use Vivarium\Dispatcher\Priority;
use Vivarium\Test\Dispatcher\Stub\GenericEvent;
use Vivarium\Test\Dispatcher\Stub\GenericEventListener;
use Vivarium\Test\Dispatcher\Stub\SpecificEvent;
use Vivarium\Test\Dispatcher\Stub\SpecificEventListener;

#[CoversClass(EventListenerMap::class)]
final class EventListenerMapTest extends TestCase
{
    public function testProvideOnEmpty(): void
    {
        $eventListenerMap = new EventListenerMap();

        $listeners = $eventListenerMap->provide(Event::class);

        static::assertTrue($listeners->isEmpty());
    }

    public function testProvideWithSubscription(): void
    {
        $eventListenerMap = new EventListenerMap();
        $eventListenerMap = $eventListenerMap->subscribe(GenericEvent::class, new GenericEventListener())
                                             ->subscribe(GenericEvent::class, new GenericEventListener())
                                             ->subscribe(GenericEvent::class, new GenericEventListener());

        $listeners = $eventListenerMap->provide(GenericEvent::class);

        static::assertCount(3, $listeners);
    }

    public function testProvideWithHierarchicalSubscription(): void
    {
        $eventListenerMap = new EventListenerMap();

        $eventListenerMap = $eventListenerMap->subscribe(GenericEvent::class, new GenericEventListener())
                                             ->subscribe(SpecificEvent::class, new SpecificEventListener());

        $genericListeners  = $eventListenerMap->provide(GenericEvent::class);
        $specificListeners = $eventListenerMap->provide(SpecificEvent::class);

        static::assertCount(1, $genericListeners);
        static::assertCount(2, $specificListeners);
    }

    public function testProvideWithUnsubscribeListener(): void
    {
        $eventListenerMap = new EventListenerMap();

        $eventListenerMap = $eventListenerMap->subscribe(GenericEvent::class, new GenericEventListener())
                                             ->subscribe(SpecificEvent::class, new SpecificEventListener())
                                             ->subscribe(SpecificEvent::class, new SpecificEventListener());

        $eventListenerMap = $eventListenerMap->unsubscribe(SpecificEvent::class, new SpecificEventListener());

        $genericListeners  = $eventListenerMap->provide(GenericEvent::class);
        $specificListeners = $eventListenerMap->provide(SpecificEvent::class);

        static::assertCount(1, $genericListeners);
        static::assertCount(2, $specificListeners);
    }

    public function testProvideWithUnsubscribedHandlersWithPriority(): void
    {
        $eventListenerMap = new EventListenerMap();

        $eventListenerMap = $eventListenerMap
            ->subscribe(GenericEvent::class, new GenericEventListener())
            ->subscribe(SpecificEvent::class, new SpecificEventListener(), Priority::VERY_LOW)
            ->subscribe(SpecificEvent::class, new SpecificEventListener());

        $eventListenerMap = $eventListenerMap
            ->unsubscribe(SpecificEvent::class, new SpecificEventListener(), Priority::VERY_LOW);

        $genericListeners  = $eventListenerMap->provide(GenericEvent::class);
        $specificListeners = $eventListenerMap->provide(SpecificEvent::class);

        static::assertCount(1, $genericListeners);
        static::assertCount(2, $specificListeners);
    }

    public function testSubscribeImmutability(): void
    {
        $eventListenerMap = new EventListenerMap();

        static::assertNotSame(
            $eventListenerMap,
            $eventListenerMap->subscribe(GenericEvent::class, new GenericEventListener()),
        );
    }

    public function testUnsubscribeImmutability(): void
    {
        $eventListenerMap = new EventListenerMap();
        $eventListenerMap = $eventListenerMap->subscribe(GenericEvent::class, new GenericEventListener());

        static::assertNotSame(
            $eventListenerMap,
            $eventListenerMap->unsubscribe(GenericEvent::class, new GenericEventListener()),
        );
    }
}
