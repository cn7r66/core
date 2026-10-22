<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Comparator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Comparator\Priority;
use Vivarium\Comparator\ValueAndPriority;

#[CoversClass(ValueAndPriority::class)]
class ValueAndPriorityTest extends TestCase
{
    public function testConstructorWithDefaultPriority(): void
    {
        $valueAndPriority = new ValueAndPriority('test');

        static::assertEquals('test', $valueAndPriority->getValue());
        static::assertEquals(Priority::NORMAL, $valueAndPriority->getPriority());
    }

    public function testConstructorWithCustomPriority(): void
    {
        $valueAndPriority = new ValueAndPriority('test', Priority::HIGH);

        static::assertEquals('test', $valueAndPriority->getValue());
        static::assertEquals(Priority::HIGH, $valueAndPriority->getPriority());
    }

    public function testEqualsWithDifferentType(): void
    {
        $valueAndPriority = new ValueAndPriority('test');

        static::assertFalse($valueAndPriority->equals(new stdClass()));
    }

    public function testEqualsWithSameInstance(): void
    {
        $valueAndPriority = new ValueAndPriority('test');

        static::assertTrue($valueAndPriority->equals($valueAndPriority));
    }

    public function testEqualsWithSameValue(): void
    {
        $first  = new ValueAndPriority('test', Priority::LOW);
        $second = new ValueAndPriority('test', Priority::HIGH);

        static::assertTrue($first->equals($second));
    }

    public function testEqualsWithDifferentValue(): void
    {
        $first  = new ValueAndPriority('foo');
        $second = new ValueAndPriority('bar');

        static::assertFalse($first->equals($second));
    }

    public function testHash(): void
    {
        $first  = new ValueAndPriority('test', Priority::LOW);
        $second = new ValueAndPriority('test', Priority::HIGH);

        static::assertEquals($first->hash(), $second->hash());
    }
}
