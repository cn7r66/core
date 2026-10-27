<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Type;

use Closure;
use Countable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Traversable;
use Vivarium\Test\Type\Stub\StubEnum;
use Vivarium\Type\Clazz;
use Vivarium\Type\Exception\InvalidType;
use Vivarium\Type\Exception\NotAPrimitive;
use Vivarium\Type\Intersection;
use Vivarium\Type\Primitive;
use Vivarium\Type\Type;
use Vivarium\Type\Union;

#[CoversClass(Primitive::class)]
final class PrimitiveTest extends TestCase
{
    #[DataProvider('provideInvalidKeywords')]
    public function testConstructorException(string $input): void
    {
        try {
            new Primitive($input);
        } catch (NotAPrimitive $e) {
            self::assertSame($input, $e->getType());
            self::assertInstanceOf(InvalidType::class, $e);
            self::assertInstanceOf(InvalidArgumentException::class, $e);

            return;
        }

        self::fail('NotAPrimitive was not thrown');
    }

    #[DataProvider('provideAccepts')]
    public function testAccepts(Primitive $target, Type $source, bool $expected): void
    {
        self::assertSame($expected, $target->accepts($source));
    }

    #[DataProvider('provideAcceptsVar')]
    public function testAcceptsVar(string $keyword, mixed $value, bool $expected): void
    {
        self::assertSame($expected, (new Primitive($keyword))->acceptsVar($value));
    }

    #[DataProvider('provideToString')]
    public function testToString(string $input, string $expected): void
    {
        self::assertSame($expected, (string) new Primitive($input));
    }

    #[DataProvider('provideEquals')]
    public function testEquals(Primitive $primitive, object $other, bool $expected): void
    {
        self::assertSame($expected, $primitive->equals($other));
    }

    #[DataProvider('provideHash')]
    public function testHash(Primitive $a, Primitive $b, bool $expectedEqual): void
    {
        if ($expectedEqual) {
            self::assertSame($a->hash(), $b->hash());

            return;
        }

        self::assertNotSame($a->hash(), $b->hash());
    }

    #[DataProvider('provideFormat')]
    public function testFormat(string $type, string $expected): void
    {
        self::assertSame($expected, Primitive::format($type));
    }

    /** @return array<string, array{string}> */
    public static function provideInvalidKeywords(): array
    {
        return [
            'integer alias' => ['integer'],
            'double alias' => ['double'],
            'boolean alias' => ['boolean'],
            'resource' => ['resource'],
            'iterable' => ['iterable'],
            'self' => ['self'],
            'parent' => ['parent'],
            'static' => ['static'],
            'class name' => [Countable::class],
            'empty string' => [''],
            'mixed case alias' => ['Integer'],
            'padded keyword' => [' int'],
        ];
    }

    /** @return array<string, array{Primitive, Type, bool}> */
    public static function provideAccepts(): array
    {
        $countable    = new Clazz(Countable::class);
        $intersection = new Intersection($countable, new Clazz(Traversable::class));

        return [
            'int accepts int' => [new Primitive('int'), new Primitive('int'), true],
            'string accepts string' => [new Primitive('string'), new Primitive('string'), true],
            'null accepts null' => [new Primitive('null'), new Primitive('null'), true],
            'int rejects string' => [new Primitive('int'), new Primitive('string'), false],
            'float accepts int' => [new Primitive('float'), new Primitive('int'), true],
            'int rejects float' => [new Primitive('int'), new Primitive('float'), false],
            'bool accepts true' => [new Primitive('bool'), new Primitive('true'), true],
            'bool accepts false' => [new Primitive('bool'), new Primitive('false'), true],
            'true rejects false' => [new Primitive('true'), new Primitive('false'), false],
            'true rejects bool' => [new Primitive('true'), new Primitive('bool'), false],
            'int accepts never' => [new Primitive('int'), new Primitive('never'), true],
            'void accepts never' => [new Primitive('void'), new Primitive('never'), true],
            'object accepts never' => [new Primitive('object'), new Primitive('never'), true],
            'never accepts never' => [new Primitive('never'), new Primitive('never'), true],
            'never rejects int' => [new Primitive('never'), new Primitive('int'), false],
            'mixed accepts int' => [new Primitive('mixed'), new Primitive('int'), true],
            'mixed accepts null' => [new Primitive('mixed'), new Primitive('null'), true],
            'mixed accepts a class' => [new Primitive('mixed'), $countable, true],
            'mixed rejects void' => [new Primitive('mixed'), new Primitive('void'), false],
            'void accepts void' => [new Primitive('void'), new Primitive('void'), true],
            'void rejects null' => [new Primitive('void'), new Primitive('null'), false],
            'null rejects void' => [new Primitive('null'), new Primitive('void'), false],
            'object accepts a class' => [new Primitive('object'), new Clazz(stdClass::class), true],
            'object accepts an interface' => [new Primitive('object'), $countable, true],
            'object accepts an enum' => [new Primitive('object'), new Clazz(StubEnum::class), true],
            'object rejects array' => [new Primitive('object'), new Primitive('array'), false],
            'callable accepts an invokable class' => [new Primitive('callable'), new Clazz(Closure::class), true],
            'callable rejects a class' => [new Primitive('callable'), new Clazz(stdClass::class), false],
            'int rejects a class' => [new Primitive('int'), $countable, false],
            'string rejects a class' => [new Primitive('string'), $countable, false],
            'array rejects a class' => [new Primitive('array'), $countable, false],
            'null rejects a class' => [new Primitive('null'), $countable, false],
            'accepts uppercase source' => [new Primitive('int'), new Primitive('NEVER'), true],
            'int rejects int|null union' => [
                new Primitive('int'),
                new Union(new Primitive('int'), new Primitive('null')),
                false,
            ],
            'mixed accepts int|null union' => [
                new Primitive('mixed'),
                new Union(new Primitive('int'), new Primitive('null')),
                true,
            ],
            'object accepts Countable|stdClass union' => [
                new Primitive('object'),
                new Union($countable, new Clazz(stdClass::class)),
                true,
            ],
            'object accepts Countable&Traversable intersection' => [new Primitive('object'), $intersection, true],
            'callable rejects Countable&Traversable intersection' => [new Primitive('callable'), $intersection, false],
            'int rejects Countable&Traversable intersection' => [new Primitive('int'), $intersection, false],
        ];
    }

    /** @return array<string, array{string, mixed, bool}> */
    public static function provideAcceptsVar(): array
    {
        $closure = static fn (): int => 1;

        return [
            'int with int' => ['int', 1, true],
            'int with integral float' => ['int', 1.0, false],
            'int with numeric string' => ['int', '1', false],
            'float with float' => ['float', 1.5, true],
            'float with int' => ['float', 1, true],
            'string with string' => ['string', 'abc', true],
            'string with numeric string' => ['string', '1', true],
            'string with int' => ['string', 1, false],
            'bool with true' => ['bool', true, true],
            'bool with false' => ['bool', false, true],
            'bool with int' => ['bool', 1, false],
            'true with true' => ['true', true, true],
            'true with false' => ['true', false, false],
            'false with false' => ['false', false, true],
            'null with null' => ['null', null, true],
            'null with false' => ['null', false, false],
            'array with array' => ['array', [1], true],
            'array with object' => ['array', new stdClass(), false],
            'object with object' => ['object', new stdClass(), true],
            'object with closure' => ['object', $closure, true],
            'object with array' => ['object', [1], false],
            'callable with closure' => ['callable', $closure, true],
            'callable with function name' => ['callable', 'strlen', true],
            'callable with plain string' => ['callable', 'abc', false],
            'mixed with null' => ['mixed', null, true],
            'mixed with object' => ['mixed', new stdClass(), true],
            'never with null' => ['never', null, false],
            'void with null' => ['void', null, false],
        ];
    }

    /** @return array<string, array{string, string}> */
    public static function provideToString(): array
    {
        return [
            'int' => ['int', 'int'],
            'float' => ['float', 'float'],
            'string' => ['string', 'string'],
            'bool' => ['bool', 'bool'],
            'true' => ['true', 'true'],
            'false' => ['false', 'false'],
            'null' => ['null', 'null'],
            'array' => ['array', 'array'],
            'object' => ['object', 'object'],
            'callable' => ['callable', 'callable'],
            'mixed' => ['mixed', 'mixed'],
            'never' => ['never', 'never'],
            'void' => ['void', 'void'],
            'uppercase' => ['INT', 'int'],
            'mixed case' => ['Mixed', 'mixed'],
            'uppercase null' => ['NULL', 'null'],
        ];
    }

    /** @return array<string, array{Primitive, object, bool}> */
    public static function provideEquals(): array
    {
        $int = new Primitive('int');

        return [
            'same instance' => [$int, $int, true],
            'same keyword' => [new Primitive('int'), new Primitive('int'), true],
            'different case' => [new Primitive('INT'), new Primitive('int'), true],
            'both uppercase' => [new Primitive('Mixed'), new Primitive('MIXED'), true],
            'different keyword' => [new Primitive('int'), new Primitive('float'), false],
            'related keyword' => [new Primitive('bool'), new Primitive('true'), false],
            'stdClass' => [new Primitive('object'), new stdClass(), false],
            'clazz with same name' => [new Primitive('object'), new Clazz(stdClass::class), false],
        ];
    }

    /** @return array<string, array{Primitive, Primitive, bool}> */
    public static function provideHash(): array
    {
        return [
            'same keyword' => [new Primitive('int'), new Primitive('int'), true],
            'different case' => [new Primitive('INT'), new Primitive('int'), true],
            'different keyword' => [new Primitive('int'), new Primitive('float'), false],
            'related keyword' => [new Primitive('bool'), new Primitive('false'), false],
        ];
    }

    /** @return array<string, array{string, string}> */
    public static function provideFormat(): array
    {
        return [
            'lowercase keyword' => ['int', 'int'],
            'uppercase keyword' => ['INT', 'int'],
            'mixed case keyword' => ['Null', 'null'],
            'not a keyword' => ['Integer', 'integer'],
        ];
    }
}
