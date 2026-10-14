<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Registry;

use PHPUnit\Framework\TestCase;
use Vivarium\Comparator\Priority;
use Vivarium\Comparator\ValueAndPriority;
use Vivarium\Container\Binding;
use Vivarium\Container\Decorator;
use Vivarium\Container\Exception\AmbiguousInjection;
use Vivarium\Container\Injection;
use Vivarium\Container\Injection\MethodCall;
use Vivarium\Container\Registry\EagerRegistry;
use Vivarium\Test\Container\Stub\StubAmbiguousFirst;
use Vivarium\Test\Container\Stub\StubAmbiguousImplementation;
use Vivarium\Test\Container\Stub\StubAmbiguousSecond;
use Vivarium\Test\Container\Stub\StubBase;
use Vivarium\Test\Container\Stub\StubClass;

use function array_map;
use function sort;

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

    /**
     * @covers ::decorate
     * @covers ::findDecorations
     * @covers ::__construct
     */
    public function testDecorationsWithEqualPriorityAreAllKept(): void
    {
        $first  = $this->createMock(Decorator::class);
        $second = $this->createMock(Decorator::class);
        $third  = $this->createMock(Decorator::class);

        $registry = (new EagerRegistry())
            ->decorate(StubClass::class)->withDecorator($first)->withPriority(5)
            ->decorate(StubClass::class)->withDecorator($second)->withPriority(5)
            ->decorate(StubClass::class)->withDecorator($third)->withPriority(5);

        $result = $this->toArray($registry->findDecorations(new Binding(StubClass::class)));

        static::assertCount(3, $result);
        static::assertSame($first, $result[0]->getValue());
        static::assertSame($second, $result[1]->getValue());
        static::assertSame($third, $result[2]->getValue());
    }

    /**
     * @covers ::decorate
     * @covers ::findDecorations
     * @covers ::__construct
     */
    public function testSameDecoratorRegisteredTwiceIsReturnedTwice(): void
    {
        $decorator = $this->createMock(Decorator::class);

        $registry = (new EagerRegistry())
            ->decorate(StubClass::class)->withDecorator($decorator)->withDefaultPriority()
            ->decorate(StubClass::class)->withDecorator($decorator)->withDefaultPriority();

        $result = $this->toArray($registry->findDecorations(new Binding(StubClass::class)));

        static::assertCount(2, $result);
        static::assertSame($decorator, $result[0]->getValue());
        static::assertSame($decorator, $result[1]->getValue());
    }

    /**
     * @dataProvider provideChannelDefaultPriorities
     * @covers ::inject
     * @covers ::call
     * @covers ::decorate
     * @covers ::findInjections
     * @covers ::findCalls
     * @covers ::findDecorations
     * @covers ::__construct
     */
    public function testDefaultPriorityDependsOnChannel(string $channel, int $expected): void
    {
        $binding = new Binding(StubClass::class);

        $registry = (new EagerRegistry())
            ->inject(StubClass::class)->withInjection($this->mockInjection('slot'))->withDefaultPriority()
            ->call(StubClass::class)->withInjection($this->mockInjection('slot'))->withDefaultPriority()
            ->decorate(StubClass::class)->withDecorator($this->createMock(Decorator::class))->withDefaultPriority();

        $found = [
            'injection' => $registry->findInjections($binding),
            'call' => $registry->findCalls($binding),
            'decoration' => $registry->findDecorations($binding),
        ];

        $result = $this->toArray($found[$channel]);

        static::assertCount(1, $result);
        static::assertSame($expected, $result[0]->getPriority());
    }

    /** @return iterable<string, array{string, int}> */
    public static function provideChannelDefaultPriorities(): iterable
    {
        yield 'injection' => ['injection', Priority::LOW];
        yield 'call' => ['call', Priority::NORMAL];
        yield 'decoration' => ['decoration', Priority::HIGH];
    }

    /**
     * @covers ::inject
     * @covers ::findInjections
     * @covers ::isMoreSpecific
     * @covers ::__construct
     */
    public function testUnrelatedSupertypesRegisteringSameSlotAreAmbiguous(): void
    {
        $registry = (new EagerRegistry())
            ->inject(StubAmbiguousFirst::class)
            ->withInjection(new MethodCall(StubAmbiguousFirst::class, 'configure'))
            ->withDefaultPriority()
            ->inject(StubAmbiguousSecond::class)
            ->withInjection(new MethodCall(StubAmbiguousSecond::class, 'configure'))
            ->withDefaultPriority();

        try {
            $this->toArray($registry->findInjections(new Binding(StubAmbiguousImplementation::class)));
            static::fail('Expected AmbiguousInjection');
        } catch (AmbiguousInjection $e) {
            $types = array_map(static function (Binding $binding): string {
                return $binding->getType();
            }, $e->getCandidates());
            sort($types);

            static::assertSame('configure', $e->getSlot());
            static::assertSame([StubAmbiguousFirst::class, StubAmbiguousSecond::class], $types);
        }
    }

    /**
     * @covers ::inject
     * @covers ::findInjections
     * @covers ::isMoreSpecific
     * @covers ::__construct
     */
    public function testRegistrationOnCommonSubtypeResolvesAmbiguity(): void
    {
        $resolving = new MethodCall(StubAmbiguousImplementation::class, 'configure');

        $registry = (new EagerRegistry())
            ->inject(StubAmbiguousFirst::class)
            ->withInjection(new MethodCall(StubAmbiguousFirst::class, 'configure'))
            ->withDefaultPriority()
            ->inject(StubAmbiguousSecond::class)
            ->withInjection(new MethodCall(StubAmbiguousSecond::class, 'configure'))
            ->withDefaultPriority()
            ->inject(StubAmbiguousImplementation::class)
            ->withInjection($resolving)
            ->withDefaultPriority();

        $result = $this->toArray($registry->findInjections(new Binding(StubAmbiguousImplementation::class)));

        static::assertCount(1, $result);
        static::assertSame($resolving, $result[0]->getValue());
    }

    /**
     * @covers ::inject
     * @covers ::findInjections
     * @covers ::isMoreSpecific
     * @covers ::__construct
     */
    public function testSubtypeWinsOverSupertypeRegisteredAtNarrowerContext(): void
    {
        $supertype = $this->mockInjection('slot');
        $subtype   = $this->mockInjection('slot');

        $registry = (new EagerRegistry())
            ->inject(StubBase::class, 'App\\Orders')->withInjection($supertype)->withDefaultPriority()
            ->inject(StubClass::class)->withInjection($subtype)->withDefaultPriority();

        $result = $this->toArray(
            $registry->findInjections(new Binding(StubClass::class, Binding::DEFAULT, 'App\\Orders')),
        );

        static::assertCount(1, $result);
        static::assertSame($subtype, $result[0]->getValue());
    }

    /**
     * @covers ::inject
     * @covers ::findInjections
     * @covers ::isMoreSpecific
     * @covers ::__construct
     */
    public function testNarrowerContextWinsForSameType(): void
    {
        $wide   = $this->mockInjection('slot');
        $narrow = $this->mockInjection('slot');

        $registry = (new EagerRegistry())
            ->inject(StubClass::class)->withInjection($wide)->withDefaultPriority()
            ->inject(StubClass::class, 'App\\Orders')->withInjection($narrow)->withDefaultPriority();

        $result = $this->toArray(
            $registry->findInjections(new Binding(StubClass::class, Binding::DEFAULT, 'App\\Orders')),
        );

        static::assertCount(1, $result);
        static::assertSame($narrow, $result[0]->getValue());
    }
}
