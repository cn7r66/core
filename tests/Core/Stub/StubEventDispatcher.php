<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Core\Stub;

use Vivarium\Dispatcher\Event;
use Vivarium\Dispatcher\EventDispatcher;

final class StubEventDispatcher implements EventDispatcher
{
    /** @var list<Event> */
    private array $dispatched = [];

    public function __construct()
    {
    }

    public function dispatch(Event $event): Event
    {
        $this->dispatched[] = $event;

        return $event;
    }

    /** @return list<Event> */
    public function getDispatched(): array
    {
        return $this->dispatched;
    }
}
