<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use Vivarium\Container\Binding;
use Vivarium\Container\Exception\AmbiguousInjection;
use Vivarium\Container\Exception\ContainerException;
use Vivarium\Test\Container\Stub\StubBase;
use Vivarium\Test\Container\Stub\StubClass;

#[CoversClass(AmbiguousInjection::class)]
final class AmbiguousInjectionTest extends TestCase
{
    public function testExposesSlotAndCandidates(): void
    {
        $candidates = [new Binding(StubClass::class), new Binding(StubBase::class)];

        $exception = new AmbiguousInjection('slot', $candidates);

        static::assertSame('slot', $exception->getSlot());
        static::assertSame($candidates, $exception->getCandidates());
    }

    public function testMessageListsCompetingRegistrations(): void
    {
        $exception = new AmbiguousInjection(
            'slot',
            [new Binding(StubClass::class), new Binding(StubBase::class)],
        );

        static::assertSame(
            'Ambiguous injection for "slot": registered on '
            . StubClass::class . ', ' . StubBase::class
            . '. Register it on a common subtype to resolve.',
            $exception->getMessage(),
        );
    }

    public function testIsAContainerException(): void
    {
        $exception = new AmbiguousInjection(
            'slot',
            [new Binding(StubClass::class), new Binding(StubBase::class)],
        );

        static::assertInstanceOf(RuntimeException::class, $exception);
        static::assertInstanceOf(ContainerException::class, $exception);
        static::assertInstanceOf(ContainerExceptionInterface::class, $exception);
    }
}
