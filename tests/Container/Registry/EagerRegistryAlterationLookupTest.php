<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Registry;

use PHPUnit\Framework\TestCase;
use Vivarium\Comparator\ValueAndPriority;
use Vivarium\Container\Binding;
use Vivarium\Container\Decorator;
use Vivarium\Container\Injection;
use Vivarium\Container\Registry\EagerRegistry;
use Vivarium\Test\Container\Stub\StubBase;
use Vivarium\Test\Container\Stub\StubClass;

use function array_map;

/** @coversDefaultClass \Vivarium\Container\Registry\EagerRegistry */
final class EagerRegistryAlterationLookupTest extends TestCase
{
    /** @covers ::findInjections */
    public function testReturnsEmptyWhenNothingRegistered(): void
    {
        $registry = new EagerRegistry();
        $binding  = new Binding(StubClass::class);

        static::assertEmpty($registry->findInjections($binding));
        static::assertEmpty($registry->findCalls($binding));
        static::assertEmpty($registry->findDecorations($binding));
    }

    /** @covers ::findCalls */
    public function testFindCallsReturnsCallRegisteredForExactBinding(): void
    {
        $injection = $this->mockInjection('slot');

        $registry = (new EagerRegistry())
            ->call(StubClass::class)
            ->withInjection($injection)
            ->withDefaultPriority();

        $result = $this->toArray($registry->findCalls(new Binding(StubClass::class)));

        static::assertCount(1, $result);
        static::assertInstanceOf(ValueAndPriority::class, $result[0]);
        static::assertSame($injection, $result[0]->getValue());
    }

    /** @covers ::findDecorations */
    public function testFindDecorationsReturnsDecorationRegisteredForExactBinding(): void
    {
        $decorator = $this->createMock(Decorator::class);

        $registry = (new EagerRegistry())
            ->decorate(StubClass::class)
            ->withDecorator($decorator)
            ->withDefaultPriority();

        $result = $this->toArray($registry->findDecorations(new Binding(StubClass::class)));

        static::assertCount(1, $result);
        static::assertSame($decorator, $result[0]->getValue());
    }

    /**
     * @covers ::findCalls
     * @covers ::findDecorations
     */
    public function testCallsAndDecorationsAreReturnedByTheirOwnChannelOnly(): void
    {
        $injection = $this->mockInjection('slot');
        $decorator = $this->createMock(Decorator::class);

        $registry = (new EagerRegistry())
            ->call(StubClass::class)->withInjection($injection)->withDefaultPriority()
            ->decorate(StubClass::class)->withDecorator($decorator)->withDefaultPriority();

        $binding     = new Binding(StubClass::class);
        $calls       = $this->toArray($registry->findCalls($binding));
        $decorations = $this->toArray($registry->findDecorations($binding));

        static::assertCount(1, $calls);
        static::assertSame($injection, $calls[0]->getValue());
        static::assertCount(1, $decorations);
        static::assertSame($decorator, $decorations[0]->getValue());
        static::assertEmpty($this->toArray($registry->findInjections($binding)));
    }

    /** @covers ::findInjections */
    public function testInjectCollectsFromParentClassBinding(): void
    {
        $injection = $this->mockInjection('slot');

        $registry = (new EagerRegistry())
            ->inject(StubBase::class)
            ->withInjection($injection)
            ->withDefaultPriority();

        $result = $this->toArray($registry->findInjections(new Binding(StubClass::class)));

        static::assertCount(1, $result);
        static::assertSame($injection, $result[0]->getValue());
    }

    /** @covers ::findCalls */
    public function testCallDoesNotCollectFromParentClassBinding(): void
    {
        $injection = $this->mockInjection('slot');

        $registry = (new EagerRegistry())
            ->call(StubBase::class)
            ->withInjection($injection)
            ->withDefaultPriority();

        $result = $this->toArray($registry->findCalls(new Binding(StubClass::class)));

        static::assertEmpty($result);
    }

    /** @covers ::findDecorations */
    public function testDecorationDoesNotCollectFromParentClassBinding(): void
    {
        $decorator = $this->createMock(Decorator::class);

        $registry = (new EagerRegistry())
            ->decorate(StubBase::class)
            ->withDecorator($decorator)
            ->withDefaultPriority();

        $result = $this->toArray($registry->findDecorations(new Binding(StubClass::class)));

        static::assertEmpty($result);
    }

    /** @covers ::findCalls */
    public function testFindCallsReturnsSortedAscendingByPriority(): void
    {
        $lowPriority  = $this->mockInjection('slot');
        $highPriority = $this->mockInjection('slot2');

        $registry = (new EagerRegistry())
            ->call(StubClass::class)->withInjection($highPriority)->withPriority(10)
            ->call(StubClass::class)->withInjection($lowPriority)->withPriority(1);

        $result = $this->toArray($registry->findCalls(new Binding(StubClass::class)));

        static::assertCount(2, $result);
        static::assertSame(1, $result[0]->getPriority());
        static::assertSame(10, $result[1]->getPriority());
    }

    /** @covers ::findInjections */
    public function testInjectWithDifferentSlotsCollectsBothFromHierarchy(): void
    {
        $parentInjection = $this->mockInjection('slotA');
        $childInjection  = $this->mockInjection('slotB');

        $registry = (new EagerRegistry())
            ->inject(StubBase::class)->withInjection($parentInjection)->withDefaultPriority()
            ->inject(StubClass::class)->withInjection($childInjection)->withDefaultPriority();

        $result = $this->toArray($registry->findInjections(new Binding(StubClass::class)));

        $values = array_map(static function (ValueAndPriority $vp): mixed {
            return $vp->getValue();
        }, $result);

        static::assertContains($parentInjection, $values);
        static::assertContains($childInjection, $values);
    }

    /** @covers ::findInjections */
    public function testInjectChildSlotShadowsParentSlot(): void
    {
        $parentInjection = $this->mockInjection('slot');
        $childInjection  = $this->mockInjection('slot');

        $registry = (new EagerRegistry())
            ->inject(StubBase::class)->withInjection($parentInjection)->withDefaultPriority()
            ->inject(StubClass::class)->withInjection($childInjection)->withDefaultPriority();

        $result = $this->toArray($registry->findInjections(new Binding(StubClass::class)));

        static::assertCount(1, $result);
        static::assertSame($childInjection, $result[0]->getValue());
    }

    /** @covers ::findCalls */
    public function testReturnsEmptyCallsForBindingWhoseChildHoldsTheCall(): void
    {
        $injection = $this->mockInjection('slot');

        $registry = (new EagerRegistry())
            ->call(StubClass::class)
            ->withInjection($injection)
            ->withDefaultPriority();

        $result = $this->toArray($registry->findCalls(new Binding(StubBase::class)));

        static::assertEmpty($result);
    }

    /** @covers ::findCalls */
    public function testCallAccumulatesMultipleRegistrationsWithSameSlot(): void
    {
        $first  = $this->mockInjection('slot');
        $second = $this->mockInjection('slot');

        $registry = (new EagerRegistry())
            ->call(StubClass::class)->withInjection($first)->withDefaultPriority()
            ->call(StubClass::class)->withInjection($second)->withDefaultPriority();

        $result = $this->toArray($registry->findCalls(new Binding(StubClass::class)));

        static::assertCount(2, $result);
    }

    private function mockInjection(string $slot): Injection
    {
        $injection = $this->createMock(Injection::class);
        $injection->method('getSlot')->willReturn($slot);

        return $injection;
    }

    /**
     * @param iterable<ValueAndPriority<mixed>> $items
     *
     * @return array<ValueAndPriority<mixed>>
     */
    private function toArray(iterable $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = $item;
        }

        return $result;
    }
}
