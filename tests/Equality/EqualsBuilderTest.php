<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Equality;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Equality\EqualsBuilder;
use Vivarium\Test\Equality\Stub\EqualityStub;
use Vivarium\Test\Equality\Stub\StubEqualToAnything;

use const INF;
use const NAN;

#[CoversClass(EqualsBuilder::class)]
final class EqualsBuilderTest extends TestCase
{
    #[DataProvider('provideEqual')]
    public function testAppend(mixed $first, mixed $second): void
    {
        self::assertTrue((new EqualsBuilder())->append($first, $second)->isEquals());
        self::assertTrue((new EqualsBuilder())->append($second, $first)->isEquals());
    }

    #[DataProvider('provideNotEqual')]
    public function testAppendRejects(mixed $first, mixed $second): void
    {
        self::assertFalse((new EqualsBuilder())->append($first, $second)->isEquals());
        self::assertFalse((new EqualsBuilder())->append($second, $first)->isEquals());
    }

    public function testAppendKeepsFirstMismatch(): void
    {
        $builder = (new EqualsBuilder())
            ->append('a', 'b')
            ->append(42, 42);

        self::assertFalse($builder->isEquals());
    }

    public function testAppendLeavesOriginalUnchanged(): void
    {
        $builder = new EqualsBuilder();

        $builder->append(1, 2);

        self::assertTrue($builder->isEquals());
    }

    /** @return array<string, array{mixed, mixed}> */
    public static function provideEqual(): array
    {
        $object = new stdClass();

        return [
            'int' => [42, 42],
            'string' => ['hello', 'hello'],
            'bool' => [false, false],
            'null' => [null, null],
            'float' => [3.5, 3.5],
            'float not a number' => [NAN, NAN],
            'float signed zero' => [0.0, -0.0],
            'float infinity' => [INF, INF],
            'empty array' => [[], []],
            'list' => [[1, 2, 3], [1, 2, 3]],
            'map' => [['a' => 1, 'b' => 2], ['a' => 1, 'b' => 2]],
            'null element' => [[null], [null]],
            'nested array' => [['a' => 1, 'b' => ['1', 'z' => 'k']], ['a' => 1, 'b' => ['1', 'z' => 'k']]],
            'same instance' => [$object, $object],
            'equality objects' => [new EqualityStub(), new EqualityStub()],
            'equality objects in arrays' => [[new EqualityStub()], [new EqualityStub()]],
        ];
    }

    /** @return array<string, array{mixed, mixed}> */
    public static function provideNotEqual(): array
    {
        return [
            'int' => [1, 2],
            'string' => ['hello', 'world'],
            'bool' => [true, false],
            'int and float' => [1, 1.0],
            'numeric string and int' => ['1', 1],
            'null and false' => [null, false],
            'float' => [3.140000001, 3.140000002],
            'float sum' => [0.1 + 0.2, 0.3],
            'list order' => [[1, 2], [2, 1]],
            'map order' => [['a' => 1, 'b' => 2], ['b' => 2, 'a' => 1]],
            'different size' => [[1, 2, 3], [1, 2, 3, 4]],
            'different key' => [['a' => 1, 'b' => 2], ['a' => 1, 'z' => 2]],
            'null element and missing key' => [[null], []],
            'nested array' => [['b' => ['z' => 'k']], ['b' => ['q' => 'k']]],
            'array and scalar' => [[1, 2, 3, 4], 5],
            'float and string' => [0.5, 'Hello'],
            'different instances' => [new stdClass(), new stdClass()],
            'equality and plain object' => [new EqualityStub(), new stdClass()],
            'object and scalar' => [new EqualityStub(), 42],
            'equality objects that disagree' => [new StubEqualToAnything(), new EqualityStub()],
        ];
    }
}
