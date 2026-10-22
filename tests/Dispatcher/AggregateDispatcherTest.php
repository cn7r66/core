<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Dispatcher;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vivarium\Dispatcher\AggregateDispatcher;
use Vivarium\Dispatcher\Event;
use Vivarium\Dispatcher\EventListener;
use Vivarium\Dispatcher\EventListenerMap;
use Vivarium\Dispatcher\EventListenerProvider;
use Vivarium\Dispatcher\Priority;
use Vivarium\Dispatcher\StoppableEvent;

#[CoversClass(AggregateDispatcher::class)]
final class AggregateDispatcherTest extends TestCase
{
    public function testEventDispatching(): void
    {
        $event = $this->createMock(Event::class);
        $event->expects(static::once())
              ->method('isPropagationStopped')
              ->willReturn(false);

        /** @var EventListener<Event>&MockObject $listener */
        $listener = $this->createMock(EventListener::class);
        $listener->expects(static::once())
                 ->method('handle')
                 ->with($event)
                 ->willReturn($event);

        $eventListenerMap = new EventListenerMap();
        $eventListenerMap = $eventListenerMap->subscribe(Event::class, $listener);

        $dispatcher = new AggregateDispatcher($eventListenerMap);

        $dispatcher->dispatch($event);
    }

    public function testStoppableEventDispatching(): void
    {
        $event = $this->createMock(Event::class);
        $event->expects(static::exactly(2))
              ->method('isPropagationStopped')
              ->willReturnOnConsecutiveCalls(false, true);

        /** @var EventListener<Event>&MockObject $listener */
        $listener = $this->createMock(EventListener::class);
        $listener->expects(static::once())
                 ->method('handle')
                 ->with($event)
                 ->willReturn($event);

        /** @var EventListener<Event>&MockObject $listenerNeverCalled */
        $listenerNeverCalled = $this->createMock(EventListener::class);
        $listenerNeverCalled->expects(static::never())
                            ->method('handle');

        $eventListenerMap = new EventListenerMap();
        $eventListenerMap = $eventListenerMap
            ->subscribe(Event::class, $listener, Priority::VERY_HIGH)
            ->subscribe(Event::class, $listenerNeverCalled, Priority::HIGH)
            ->subscribe(Event::class, $listenerNeverCalled, Priority::HIGH);

        $dispatcher = new AggregateDispatcher($eventListenerMap);

        $dispatcher->dispatch($event);
    }

    public function testDispatchWithNoListener(): void
    {
        $dispatcher = new AggregateDispatcher(
            $this->createStub(EventListenerProvider::class),
        );

        $event       = $this->createStub(Event::class);
        $eventReturn = $dispatcher->dispatch($event);

        static::assertSame($event, $eventReturn);
    }

    public function testEventDispatchingWithDifferentPriorities(): void
    {
        $event = $this->createMock(StoppableEvent::class);
        $event->expects(static::exactly(4))
              ->method('isPropagationStopped')
              ->willReturnOnConsecutiveCalls(false, false, false, true);

        $event->expects(static::exactly(1))
              ->method('stopPropagation');

        /** @var EventListener<Event>&MockObject $listener */
        $listener = $this->createMock(EventListener::class);
        $listener->expects(static::exactly(2))
                 ->method('handle')
                 ->with($event)
                 ->willReturn($event);

        /** @var EventListener<StoppableEvent>&MockObject $stopper */
        $stopper = $this->createMock(EventListener::class);
        $stopper->expects(static::once())
                ->method('handle')
                ->with($event)
                ->willReturnCallback(
                    static function (StoppableEvent $event): StoppableEvent {
                        $event->stopPropagation();

                        return $event;
                    },
                );

        $eventListenerMap = new EventListenerMap();
        $eventListenerMap = $eventListenerMap
            ->subscribe(Event::class, $listener, Priority::VERY_LOW)
            ->subscribe(StoppableEvent::class, $stopper)
            ->subscribe(Event::class, $listener, Priority::HIGH)
            ->subscribe(Event::class, $listener, Priority::VERY_HIGH);

        $dispatcher = new AggregateDispatcher($eventListenerMap);

        $dispatcher->dispatch($event);
    }

    public function testEventDispatchingWithDifferentPrioritiesFromDifferentProviders(): void
    {
        $event = $this->createMock(StoppableEvent::class);
        $event->expects(static::exactly(4))
              ->method('isPropagationStopped')
              ->willReturnOnConsecutiveCalls(false, false, false, true);

        $event->expects(static::exactly(1))
              ->method('stopPropagation');

        /** @var EventListener<Event>&MockObject $listener */
        $listener = $this->createMock(EventListener::class);
        $listener->expects(static::exactly(2))
                 ->method('handle')
                 ->with($event)
                 ->willReturn($event);

        /** @var EventListener<StoppableEvent>&MockObject $stopper */
        $stopper = $this->createMock(EventListener::class);
        $stopper->expects(static::once())
                ->method('handle')
                ->with($event)
                ->willReturnCallback(
                    static function (StoppableEvent $event): StoppableEvent {
                        $event->stopPropagation();

                        return $event;
                    },
                );

        $eventListenerMap1 = new EventListenerMap();
        $eventListenerMap2 = new EventListenerMap();

        $eventListenerMap1 = $eventListenerMap1
            ->subscribe(Event::class, $listener, Priority::VERY_HIGH)
            ->subscribe(Event::class, $listener, Priority::VERY_HIGH);

        $eventListenerMap2 = $eventListenerMap2
            ->subscribe(StoppableEvent::class, $stopper, Priority::HIGH)
            ->subscribe(Event::class, $listener)
            ->subscribe(Event::class, $listener);

        $dispatcher = new AggregateDispatcher($eventListenerMap1, $eventListenerMap2);

        $dispatcher->dispatch($event);
    }
}
