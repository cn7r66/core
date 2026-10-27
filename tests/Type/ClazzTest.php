<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Type;

use ArrayAccess;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SplObjectStorage;
use stdClass;
use Traversable;
use Vivarium\Test\Type\Stub\StubClass;
use Vivarium\Test\Type\Stub\StubEnum;
use Vivarium\Test\Type\Stub\StubInterfaceA;
use Vivarium\Test\Type\Stub\StubInvokable;
use Vivarium\Test\Type\Stub\StubReflectionShared;
use Vivarium\Test\Type\Stub\StubSubclass;
use Vivarium\Type\Clazz;
use Vivarium\Type\Exception\InvalidType;
use Vivarium\Type\Exception\NotAClass;
use Vivarium\Type\Intersection;
use Vivarium\Type\Primitive;
use Vivarium\Type\Type;
use Vivarium\Type\Union;

#[CoversClass(Clazz::class)]
final class ClazzTest extends TestCase
{
    #[DataProvider('provideInvalidClasses')]
    public function testConstructorException(string $input, string $message): void
    {
        try {
            new Clazz($input);
        } catch (NotAClass $e) {
            self::assertSame($input, $e->getClass());
            self::assertSame($message, $e->getMessage());
            self::assertInstanceOf(InvalidType::class, $e);
            self::assertInstanceOf(InvalidArgumentException::class, $e);

            return;
        }

        self::fail('NotAClass was not thrown');
    }

    #[DataProvider('provideToString')]
    public function testGetReflection(string $input, string $expectedName): void
    {
        $reflection = (new Clazz($input))->getReflection();

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame($expectedName, $reflection->getName());
    }

    public function testGetReflectionReturnsNewInstance(): void
    {
        $clazz = new Clazz(StubClass::class);

        self::assertNotSame($clazz->getReflection(), $clazz->getReflection());
    }

    #[DataProvider('provideAccepts')]
    public function testAccepts(Clazz $target, Type $source, bool $expected): void
    {
        self::assertSame($expected, $target->accepts($source));
    }

    #[DataProvider('provideAcceptsVar')]
    public function testAcceptsVar(Clazz $target, mixed $value, bool $expected): void
    {
        self::assertSame($expected, $target->acceptsVar($value));
    }

    #[DataProvider('provideToString')]
    public function testToString(string $input, string $expected): void
    {
        self::assertSame($expected, (string) new Clazz($input));
    }

    #[DataProvider('provideEquals')]
    public function testEquals(Clazz $clazz, object $other, bool $expected): void
    {
        self::assertSame($expected, $clazz->equals($other));
    }

    #[DataProvider('provideHash')]
    public function testHash(Clazz $a, Clazz $b, bool $expectedEqual): void
    {
        if ($expectedEqual) {
            self::assertSame($a->hash(), $b->hash());

            return;
        }

        self::assertNotSame($a->hash(), $b->hash());
    }

    #[DataProvider('provideFormat')]
    public function testFormat(string $class, string $expected): void
    {
        self::assertSame($expected, Clazz::format($class));
    }

    /** @return array<string, array{string, string}> */
    public static function provideInvalidClasses(): array
    {
        $trait   = StubReflectionShared::class;
        $unknown = 'Vivarium\Test\Type\Stub\DoesNotExist';

        return [
            'trait' => [
                $trait,
                'Expected "' . $trait . '" to be a class, interface or enum. Got a trait.',
            ],
            'trait with leading backslash' => [
                '\\' . $trait,
                'Expected "\\' . $trait . '" to be a class, interface or enum. Got a trait.',
            ],
            'unknown class' => [
                $unknown,
                'Expected "' . $unknown . '" to be an existing class, interface or enum.',
            ],
            'int keyword' => ['int', 'Expected "int" to be an existing class, interface or enum.'],
            'empty string' => ['', 'Expected "" to be an existing class, interface or enum.'],
        ];
    }

    /** @return array<string, array{string, string}> */
    public static function provideToString(): array
    {
        return [
            'class' => [StubClass::class, StubClass::class],
            'class with leading backslash' => ['\\' . StubClass::class, StubClass::class],
            'class lowercase' => ['vivarium\test\type\stub\stubclass', StubClass::class],
            'class uppercase' => ['VIVARIUM\TEST\TYPE\STUB\STUBCLASS', StubClass::class],
            'class lowercase with leading backslash' => ['\vivarium\test\type\stub\stubclass', StubClass::class],
            'subclass' => [StubSubclass::class, StubSubclass::class],
            'interface' => [StubInterfaceA::class, StubInterfaceA::class],
            'interface lowercase' => ['vivarium\test\type\stub\stubinterfacea', StubInterfaceA::class],
            'enum' => [StubEnum::class, StubEnum::class],
            'enum lowercase' => ['vivarium\test\type\stub\stubenum', StubEnum::class],
            'invokable' => [StubInvokable::class, StubInvokable::class],
            'builtin' => [stdClass::class, 'stdClass'],
            'builtin with leading backslash' => ['\stdClass', 'stdClass'],
            'builtin lowercase with leading backslash' => ['\stdclass', 'stdClass'],
            'builtin lowercase' => ['stdclass', 'stdClass'],
        ];
    }

    /** @return array<string, array{Clazz, Type, bool}> */
    public static function provideAccepts(): array
    {
        return [
            'class accepts itself' => [new Clazz(StubClass::class), new Clazz(StubClass::class), true],
            'class accepts subclass' => [new Clazz(StubClass::class), new Clazz(StubSubclass::class), true],
            'class rejects parent' => [new Clazz(StubSubclass::class), new Clazz(StubClass::class), false],
            'class rejects unrelated class' => [new Clazz(StubClass::class), new Clazz(StubInvokable::class), false],
            'class rejects interface it implements' => [
                new Clazz(StubClass::class),
                new Clazz(StubInterfaceA::class),
                false,
            ],
            'interface accepts itself' => [new Clazz(StubInterfaceA::class), new Clazz(StubInterfaceA::class), true],
            'interface accepts implementor' => [new Clazz(StubInterfaceA::class), new Clazz(StubClass::class), true],
            'interface accepts implementor subclass' => [
                new Clazz(StubInterfaceA::class),
                new Clazz(StubSubclass::class),
                true,
            ],
            'interface rejects unrelated class' => [
                new Clazz(StubInterfaceA::class),
                new Clazz(StubInvokable::class),
                false,
            ],
            'enum accepts itself' => [new Clazz(StubEnum::class), new Clazz(StubEnum::class), true],
            'enum rejects class' => [new Clazz(StubEnum::class), new Clazz(StubClass::class), false],
            'class rejects enum' => [new Clazz(StubClass::class), new Clazz(StubEnum::class), false],
            'different spelling of same class' => [
                new Clazz(StubClass::class),
                new Clazz('\\' . StubClass::class),
                true,
            ],
            'class accepts never' => [new Clazz(StubClass::class), new Primitive('never'), true],
            'interface accepts never' => [new Clazz(StubInterfaceA::class), new Primitive('never'), true],
            'enum accepts never' => [new Clazz(StubEnum::class), new Primitive('never'), true],
            'class rejects object' => [new Clazz(StubClass::class), new Primitive('object'), false],
            'class rejects mixed' => [new Clazz(StubClass::class), new Primitive('mixed'), false],
            'class rejects callable' => [new Clazz(StubInvokable::class), new Primitive('callable'), false],
            'class rejects null' => [new Clazz(StubClass::class), new Primitive('null'), false],
            'class rejects int' => [new Clazz(StubClass::class), new Primitive('int'), false],
            'class rejects void' => [new Clazz(StubClass::class), new Primitive('void'), false],
            'Countable accepts ArrayIterator|SplObjectStorage union' => [
                new Clazz(Countable::class),
                new Union(new Clazz(ArrayIterator::class), new Clazz(SplObjectStorage::class)),
                true,
            ],
            'Countable rejects ArrayIterator|stdClass union' => [
                new Clazz(Countable::class),
                new Union(new Clazz(ArrayIterator::class), new Clazz(stdClass::class)),
                false,
            ],
            'Countable accepts Countable&Traversable intersection' => [
                new Clazz(Countable::class),
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                true,
            ],
            'ArrayAccess rejects Countable&Traversable intersection' => [
                new Clazz(ArrayAccess::class),
                new Intersection(new Clazz(Countable::class), new Clazz(Traversable::class)),
                false,
            ],
        ];
    }

    /** @return array<string, array{Clazz, mixed, bool}> */
    public static function provideAcceptsVar(): array
    {
        return [
            'class accepts instance' => [new Clazz(StubClass::class), new StubClass(), true],
            'class accepts subclass instance' => [new Clazz(StubClass::class), new StubSubclass(), true],
            'subclass rejects parent instance' => [new Clazz(StubSubclass::class), new StubClass(), false],
            'class rejects unrelated instance' => [new Clazz(StubClass::class), new StubInvokable(), false],
            'class rejects stdClass' => [new Clazz(StubClass::class), new stdClass(), false],
            'interface accepts implementor' => [new Clazz(StubInterfaceA::class), new StubClass(), true],
            'interface accepts implementor subclass' => [new Clazz(StubInterfaceA::class), new StubSubclass(), true],
            'interface rejects non implementor' => [new Clazz(StubInterfaceA::class), new StubInvokable(), false],
            'enum accepts its case' => [new Clazz(StubEnum::class), StubEnum::First, true],
            'enum accepts another case' => [new Clazz(StubEnum::class), StubEnum::Second, true],
            'enum rejects class instance' => [new Clazz(StubEnum::class), new StubClass(), false],
            'class rejects enum case' => [new Clazz(StubClass::class), StubEnum::First, false],
            'class rejects null' => [new Clazz(StubClass::class), null, false],
            'class rejects int' => [new Clazz(StubClass::class), 1, false],
            'class rejects float' => [new Clazz(StubClass::class), 1.5, false],
            'class rejects bool' => [new Clazz(StubClass::class), true, false],
            'class rejects array' => [new Clazz(StubClass::class), [new StubClass()], false],
            'class rejects class name string' => [new Clazz(StubClass::class), StubClass::class, false],
            'interface rejects null' => [new Clazz(StubInterfaceA::class), null, false],
            'enum rejects string' => [new Clazz(StubEnum::class), 'First', false],
        ];
    }

    /** @return array<string, array{Clazz, object, bool}> */
    public static function provideEquals(): array
    {
        $class = new Clazz(StubClass::class);

        return [
            'same instance' => [$class, $class, true],
            'same class' => [new Clazz(StubClass::class), new Clazz(StubClass::class), true],
            'leading backslash' => [new Clazz('\stdClass'), new Clazz('stdclass'), true],
            'different case' => [new Clazz('STDCLASS'), new Clazz('\stdClass'), true],
            'enum spellings' => [
                new Clazz(StubEnum::class),
                new Clazz('\vivarium\test\type\stub\stubenum'),
                true,
            ],
            'different classes' => [new Clazz(StubClass::class), new Clazz(StubInvokable::class), false],
            'parent and subclass' => [new Clazz(StubClass::class), new Clazz(StubSubclass::class), false],
            'class and interface' => [new Clazz(StubClass::class), new Clazz(StubInterfaceA::class), false],
            'primitive' => [new Clazz(stdClass::class), new Primitive('object'), false],
            'instance of the class' => [new Clazz(StubClass::class), new StubClass(), false],
            'stdClass' => [new Clazz(stdClass::class), new stdClass(), false],
        ];
    }

    /** @return array<string, array{Clazz, Clazz, bool}> */
    public static function provideHash(): array
    {
        return [
            'same class' => [new Clazz(StubClass::class), new Clazz(StubClass::class), true],
            'leading backslash' => [new Clazz('\stdClass'), new Clazz('stdclass'), true],
            'different case' => [new Clazz('STDCLASS'), new Clazz('stdClass'), true],
            'different classes' => [new Clazz(StubClass::class), new Clazz(StubInvokable::class), false],
            'parent and subclass' => [new Clazz(StubClass::class), new Clazz(StubSubclass::class), false],
            'class and interface' => [new Clazz(StubClass::class), new Clazz(StubInterfaceA::class), false],
        ];
    }

    /** @return array<string, array{string, string}> */
    public static function provideFormat(): array
    {
        return [
            'declared name' => [Countable::class, Countable::class],
            'lowercase interface' => ['countable', Countable::class],
            'leading backslash' => ['\Countable', Countable::class],
            'lowercase class' => ['stdclass', stdClass::class],
            'lowercase enum' => ['vivarium\test\type\stub\stubenum', StubEnum::class],
            'unknown name' => ['Nope', 'Nope'],
            'trait' => [StubReflectionShared::class, StubReflectionShared::class],
        ];
    }
}
