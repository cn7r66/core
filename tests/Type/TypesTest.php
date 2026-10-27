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
use LogicException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClassConstant;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use stdClass;
use Vivarium\Test\Type\Stub\StubClass;
use Vivarium\Test\Type\Stub\StubReflectionChild;
use Vivarium\Test\Type\Stub\StubReflectionClosureFactory;
use Vivarium\Test\Type\Stub\StubReflectionGrandchild;
use Vivarium\Test\Type\Stub\StubReflectionParent;
use Vivarium\Test\Type\Stub\StubReflectionTraitUser;
use Vivarium\Type\Clazz;
use Vivarium\Type\Exception\InvalidType;
use Vivarium\Type\Exception\NotAClass;
use Vivarium\Type\Exception\NotAnIntersection;
use Vivarium\Type\Exception\NotAType;
use Vivarium\Type\Exception\NotAUnion;
use Vivarium\Type\Intersection;
use Vivarium\Type\Primitive;
use Vivarium\Type\Type;
use Vivarium\Type\Types;
use Vivarium\Type\Union;

use function fclose;
use function fopen;
use function str_contains;

#[CoversClass(Types::class)]
final class TypesTest extends TestCase
{
    /** @param class-string<Type> $kind */
    #[DataProvider('provideParsedTypes')]
    public function testFromString(string $input, string $expected, string $kind): void
    {
        $type = Types::fromString($input);

        self::assertInstanceOf($kind, $type);
        self::assertSame($expected, (string) $type);
        self::assertTrue($type->equals(Types::fromString((string) $type)));
    }

    #[DataProvider('provideSyntaxErrors')]
    public function testFromStringException(string $input, string $reason): void
    {
        try {
            Types::fromString($input);
        } catch (NotAType $e) {
            self::assertInstanceOf(InvalidType::class, $e);
            self::assertInstanceOf(InvalidArgumentException::class, $e);
            self::assertSame($input, $e->getType());
            self::assertSame($reason, $e->getReason());
            self::assertSame('Expected "' . $input . '" to be a type: ' . $reason . '.', $e->getMessage());

            return;
        }

        self::fail('NotAType was not thrown');
    }

    /** @param class-string<InvalidType> $exception */
    #[DataProvider('provideNodeErrors')]
    public function testFromStringNodeException(string $input, string $exception, string $fragment): void
    {
        try {
            Types::fromString($input);
        } catch (InvalidType $e) {
            self::assertInstanceOf($exception, $e);
            self::assertTrue(str_contains($e->getMessage(), $fragment));

            return;
        }

        self::fail($exception . ' was not thrown');
    }

    #[DataProvider('provideParameters')]
    public function testOfParameter(ReflectionParameter $parameter, string $expected): void
    {
        self::assertSame($expected, (string) Types::ofParameter($parameter));
    }

    #[DataProvider('provideUnresolvableParameters')]
    public function testOfParameterException(ReflectionParameter $parameter, string $type, string $reason): void
    {
        try {
            Types::ofParameter($parameter);
        } catch (NotAType $e) {
            self::assertSame($type, $e->getType());
            self::assertSame($reason, $e->getReason());

            return;
        }

        self::fail('NotAType was not thrown');
    }

    public function testOfProperty(): void
    {
        $property = new ReflectionProperty(StubReflectionChild::class, 'property');

        self::assertSame(StubReflectionChild::class . '|null', (string) Types::ofProperty($property));
    }

    #[DataProvider('provideReturns')]
    public function testOfReturn(ReflectionFunctionAbstract $function, string $called, string $expected): void
    {
        self::assertSame($expected, (string) Types::ofReturn($function, $called));
    }

    public function testOfReturnException(): void
    {
        $closure = Closure::bind(static function (): self {
            throw new LogicException();
        }, null, null);
        self::assertInstanceOf(Closure::class, $closure);

        try {
            Types::ofReturn(new ReflectionFunction($closure), StubReflectionChild::class);
        } catch (NotAType $e) {
            self::assertSame('self cannot be resolved without a class', $e->getReason());

            return;
        }

        self::fail('NotAType was not thrown');
    }

    #[DataProvider('provideClassConstants')]
    public function testOfClassConstant(string $name, string $expected): void
    {
        $constant = new ReflectionClassConstant(StubReflectionChild::class, $name);

        self::assertSame($expected, (string) Types::ofClassConstant($constant));
    }

    #[DataProvider('provideValues')]
    public function testOf(mixed $value, string $expected): void
    {
        self::assertSame($expected, (string) Types::of($value));
    }

    public function testOfAnonymousClass(): void
    {
        $value = new class implements Countable {
            #[Override]
            public function count(): int
            {
                return 0;
            }
        };

        $type = Types::of($value);

        self::assertInstanceOf(Clazz::class, $type);
        self::assertTrue((new Clazz(Countable::class))->accepts($type));
        self::assertTrue($type->acceptsVar($value));
    }

    public function testOfException(): void
    {
        $resource = fopen('php://memory', 'r');
        self::assertNotFalse($resource);

        try {
            Types::of($resource);
        } catch (NotAType $e) {
            self::assertSame('resource (stream)', $e->getType());
            self::assertSame('resources have no type', $e->getReason());

            return;
        } finally {
            fclose($resource);
        }

        self::fail('NotAType was not thrown');
    }

    /**
     * @param list<Type>   $types
     * @param class-string $kind
     */
    #[DataProvider('provideAnyOf')]
    public function testAnyOf(array $types, string $expected, string $kind): void
    {
        $type = Types::anyOf(...$types);

        self::assertInstanceOf($kind, $type);
        self::assertSame($expected, (string) $type);
    }

    /** @param list<Type> $types */
    #[DataProvider('provideInvalidAnyOf')]
    public function testAnyOfException(array $types, string $fragment): void
    {
        try {
            Types::anyOf(...$types);
        } catch (NotAUnion $e) {
            self::assertInstanceOf(InvalidType::class, $e);
            self::assertTrue(str_contains($e->getMessage(), $fragment));

            return;
        }

        self::fail('NotAUnion was not thrown');
    }

    /**
     * @param list<Type>   $types
     * @param class-string $kind
     */
    #[DataProvider('provideAllOf')]
    public function testAllOf(array $types, string $expected, string $kind): void
    {
        $type = Types::allOf(...$types);

        self::assertInstanceOf($kind, $type);
        self::assertSame($expected, (string) $type);
    }

    /** @param list<Type> $types */
    #[DataProvider('provideInvalidAllOf')]
    public function testAllOfException(array $types, string $message): void
    {
        try {
            Types::allOf(...$types);
        } catch (NotAnIntersection $e) {
            self::assertInstanceOf(InvalidType::class, $e);
            self::assertSame($message, $e->getMessage());

            return;
        }

        self::fail('NotAnIntersection was not thrown');
    }

    /** @return array<string, array{string, string, class-string<Type>}> */
    public static function provideParsedTypes(): array
    {
        return [
            'uppercase keyword' => ['INT', 'int', Primitive::class],
            'question mark' => ['?int', 'int|null', Union::class],
            'question mark with space' => ['? int', 'int|null', Union::class],
            'spaced union' => ['int | null', 'int|null', Union::class],
            'canonical member order' => ['int|string|null', 'int|null|string', Union::class],
            'leading backslash' => ['\Countable', 'Countable', Clazz::class],
            'iterable' => ['iterable', 'Traversable|array', Union::class],
            'iterable in union' => ['iterable|int', 'Traversable|array|int', Union::class],
            'nullable iterable' => ['?iterable', 'Traversable|array|null', Union::class],
            'dnf' => ['(Countable&Traversable)|null', '(Countable&Traversable)|null', Union::class],
            'bare intersection' => ['Countable&Traversable', 'Countable&Traversable', Intersection::class],
            'spaced intersection' => ['Countable & Traversable', 'Countable&Traversable', Intersection::class],
            'group alone' => ['(Countable&Traversable)', 'Countable&Traversable', Intersection::class],
            'unparenthesized dnf' => ['Countable&Traversable|int', '(Countable&Traversable)|int', Union::class],
            'group without intersection' => ['(Countable)|int', 'Countable|int', Union::class],
            'nullable intersection' => ['?Countable&Traversable', '(Countable&Traversable)|null', Union::class],
            'two groups' => [
                '(Countable&Traversable)|(ArrayAccess&Iterator)',
                '(ArrayAccess&Iterator)|(Countable&Traversable)',
                Union::class,
            ],
            'uppercase iterable' => ['ITERABLE', 'Traversable|array', Union::class],
        ];
    }

    /** @return array<string, array{string, string}> */
    public static function provideSyntaxErrors(): array
    {
        return [
            'empty' => ['', 'empty type'],
            'trailing union bar' => ['int|', 'empty type'],
            'leading union bar' => ['|int', 'empty type'],
            'trailing ampersand' => ['Countable&', 'empty type'],
            'self' => ['self', 'self cannot be resolved without a class'],
            'uppercase self' => ['SELF', 'self cannot be resolved without a class'],
            'parent' => ['parent', 'parent cannot be resolved without a class'],
            'static' => ['static', 'static cannot be resolved without a class'],
        ];
    }

    /** @return array<string, array{string, class-string<InvalidType>, string}> */
    public static function provideNodeErrors(): array
    {
        return [
            'duplicate member' => ['int|int', NotAUnion::class, 'duplicate type int'],
            'mixed in union' => ['mixed|int', NotAUnion::class, ''],
            'unknown class' => ['Nope', NotAClass::class, ''],
            'unclosed group' => ['(Countable&Traversable', NotAClass::class, 'Expected "(Countable"'],
            'nested group' => ['((Countable&Traversable))|int', NotAClass::class, 'Expected "(Countable"'],
            'union inside a group' => ['(Countable|Traversable)&int', NotAClass::class, 'Expected "(Countable"'],
            'leading digit' => ['1abc', NotAClass::class, 'Expected "1abc"'],
            'two names' => ['int int', NotAClass::class, 'Expected "int int"'],
            'generic' => ['Countable<int>', NotAClass::class, 'Expected "Countable<int>"'],
            'qualified keyword' => ['\int', NotAClass::class, 'Expected "\int"'],
            'nullable union with null' => ['?int|null', NotAUnion::class, 'duplicate type null'],
            'iterable with array' => ['iterable|array', NotAUnion::class, 'duplicate type array'],
            'primitive in intersection' => [
                'int&string',
                NotAnIntersection::class,
                'int cannot be part of an intersection type',
            ],
            'iterable in intersection' => [
                'iterable&Countable',
                NotAnIntersection::class,
                'Expected "(Traversable|array)&Countable" to be a valid intersection: '
                    . 'Traversable|array cannot be part of an intersection type.',
            ],
        ];
    }

    /** @return array<string, array{ReflectionParameter, string}> */
    public static function provideParameters(): array
    {
        $closure     = (new StubReflectionClosureFactory())->create();
        $method      = new ReflectionMethod(StubReflectionChild::class, 'method');
        $traitMethod = new ReflectionMethod(StubReflectionTraitUser::class, 'method');
        $contextual  = (new ReflectionFunction(StubReflectionChild::contextual()))->getParameters();

        return [
            'self' => [$method->getParameters()[0], StubReflectionChild::class],
            'parent' => [$method->getParameters()[1], StubReflectionParent::class],
            'nullable own class' => [$method->getParameters()[2], StubReflectionChild::class . '|null'],
            'mixed' => [$method->getParameters()[3], 'mixed'],
            'null or int' => [$method->getParameters()[4], 'int|null'],
            'no hint' => [$method->getParameters()[5], 'mixed'],
            'iterable' => [$method->getParameters()[6], 'Traversable|array'],
            'nullable iterable' => [$method->getParameters()[7], 'Traversable|array|null'],
            'dnf' => [$method->getParameters()[8], '(Countable&Traversable)|null'],
            'scalar union' => [$method->getParameters()[9], 'int|null|string'],
            'trait self' => [$traitMethod->getParameters()[0], StubReflectionTraitUser::class],
            'closure nullable parent' => [$contextual[0], StubReflectionParent::class . '|null'],
            'closure self in a union' => [$contextual[1], StubReflectionChild::class . '|int'],
            'closure nullable self' => [$contextual[2], StubReflectionChild::class . '|null'],
            'closure self' => [
                (new ReflectionFunction($closure))->getParameters()[0],
                StubReflectionClosureFactory::class,
            ],
        ];
    }

    /** @return array<string, array{ReflectionParameter, string, string}> */
    public static function provideUnresolvableParameters(): array
    {
        $closure = Closure::bind(static function (self $x): void {
        }, null, null);
        self::assertInstanceOf(Closure::class, $closure);
        $orphan = Closure::bind(StubReflectionChild::contextual(), null, StubReflectionTraitUser::class);
        self::assertInstanceOf(Closure::class, $orphan);

        return [
            'self in a closure without scope' => [
                (new ReflectionFunction($closure))->getParameters()[0],
                'self',
                'self cannot be resolved without a class',
            ],
            'parent in a class without parent' => [
                (new ReflectionFunction($orphan))->getParameters()[0],
                '?parent',
                StubReflectionTraitUser::class . ' has no parent class',
            ],
        ];
    }

    /** @return array<string, array{ReflectionFunctionAbstract, string, string}> */
    public static function provideReturns(): array
    {
        $closure = (new StubReflectionClosureFactory())->create();

        return [
            'static' => [
                new ReflectionMethod(StubReflectionChild::class, 'method'),
                StubReflectionChild::class,
                StubReflectionChild::class,
            ],
            'static through subclass' => [
                new ReflectionMethod(StubReflectionChild::class, 'method'),
                StubReflectionGrandchild::class,
                StubReflectionGrandchild::class,
            ],
            'trait self' => [
                new ReflectionMethod(StubReflectionTraitUser::class, 'method'),
                StubReflectionTraitUser::class,
                StubReflectionTraitUser::class,
            ],
            'closure static' => [
                new ReflectionFunction($closure),
                StubReflectionGrandchild::class,
                StubReflectionGrandchild::class,
            ],
        ];
    }

    /** @return array<string, array{string, string}> */
    public static function provideClassConstants(): array
    {
        return [
            'typed' => ['TYPED', 'int'],
            'untyped' => ['UNTYPED', 'string'],
        ];
    }

    /** @return array<string, array{mixed, string}> */
    public static function provideValues(): array
    {
        return [
            'int' => [1, 'int'],
            'float' => [1.5, 'float'],
            'string' => ['x', 'string'],
            'bool' => [true, 'bool'],
            'null' => [null, 'null'],
            'array' => [[1], 'array'],
            'stdClass' => [new stdClass(), 'stdClass'],
            'stub class' => [new StubClass(), StubClass::class],
        ];
    }

    /** @return array<string, array{list<Type>, string, class-string<Type>}> */
    public static function provideAnyOf(): array
    {
        return [
            'mixed absorbs' => [
                [new Primitive('int'), new Primitive('int'), new Primitive('mixed')],
                'mixed',
                Primitive::class,
            ],
            'true false null' => [
                [new Primitive('true'), new Primitive('false'), new Primitive('null')],
                'bool|null',
                Union::class,
            ],
            'bool and true' => [[new Primitive('bool'), new Primitive('true')], 'bool', Primitive::class],
            'object absorbs class' => [
                [new Clazz(Countable::class), new Primitive('object')],
                'object',
                Primitive::class,
            ],
            'never dropped' => [[new Primitive('never'), new Primitive('int')], 'int', Primitive::class],
            'only never' => [[new Primitive('never'), new Primitive('never')], 'never', Primitive::class],
            'flattens union' => [
                [new Primitive('int'), new Union(new Primitive('int'), new Primitive('null'))],
                'int|null',
                Union::class,
            ],
            'class absorbs intersection' => [
                [new Intersection(new Clazz(Countable::class), new Clazz('Traversable')), new Clazz(Countable::class)],
                'Countable',
                Clazz::class,
            ],
            'single member' => [[new Primitive('int')], 'int', Primitive::class],
            'int and float' => [[new Primitive('int'), new Primitive('float')], 'float|int', Union::class],
        ];
    }

    /** @return array<string, array{list<Type>, string}> */
    public static function provideInvalidAnyOf(): array
    {
        return [
            'void with int' => [
                [new Primitive('void'), new Primitive('int')],
                'void can only be used as a standalone type',
            ],
            'mixed with void' => [
                [new Primitive('mixed'), new Primitive('void')],
                'mixed can only be used as a standalone type',
            ],
        ];
    }

    /** @return array<string, array{list<Type>, string, class-string<Type>}> */
    public static function provideAllOf(): array
    {
        return [
            'duplicate by case' => [
                [new Clazz(Countable::class), new Clazz('countable')],
                'Countable',
                Clazz::class,
            ],
            'flattens intersection' => [
                [
                    new Clazz(Countable::class),
                    new Intersection(new Clazz(Countable::class), new Clazz('Traversable')),
                ],
                'Countable&Traversable',
                Intersection::class,
            ],
        ];
    }

    /** @return array<string, array{list<Type>, string}> */
    public static function provideInvalidAllOf(): array
    {
        return [
            'primitive member' => [
                [new Clazz(Countable::class), new Primitive('int')],
                'Expected "Countable&int" to be a valid intersection: int cannot be part of an intersection type.',
            ],
        ];
    }
}
