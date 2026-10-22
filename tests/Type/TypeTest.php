<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Type;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionType;
use Vivarium\Test\Assertion\Stub\StubClass;
use Vivarium\Test\Type\Stub\StubInterfaceA;
use Vivarium\Test\Type\Stub\StubInterfaceB;
use Vivarium\Test\Type\Stub\TypeStub;
use Vivarium\Type\Exception\NoSuchParameter;
use Vivarium\Type\Exception\NotAType;
use Vivarium\Type\Exception\UnsupportedReflectionType;
use Vivarium\Type\Type;

#[CoversClass(Type::class)]
final class TypeTest extends TestCase
{
    #[DataProvider('provideLiterals')]
    public function testToLiteral(mixed $value, string $literal): void
    {
        static::assertSame($literal, Type::toLiteral($value));
    }

    #[DataProvider('provideStrings')]
    public function testToString(mixed $value, string $string): void
    {
        static::assertSame($string, Type::toString($value));
    }

    public function testCanonical(): void
    {
        $canonical = Type::canonical();

        static::assertContains(Type::INT, $canonical);
        static::assertContains(Type::FLOAT, $canonical);
        static::assertContains(Type::STRING, $canonical);
        static::assertContains(Type::BOOL, $canonical);
        static::assertContains(Type::ARRAY, $canonical);
        static::assertContains(Type::OBJECT, $canonical);
        static::assertContains(Type::CALLABLE, $canonical);
        static::assertContains(Type::MIXED, $canonical);

        static::assertNotContains('integer', $canonical);
        static::assertNotContains('double', $canonical);
        static::assertNotContains('boolean', $canonical);
    }

    public function testExpanded(): void
    {
        $expanded = Type::expanded();

        static::assertContains(Type::INT, $expanded);
        static::assertContains(Type::FLOAT, $expanded);
        static::assertContains(Type::STRING, $expanded);
        static::assertContains(Type::BOOL, $expanded);
        static::assertContains(Type::MIXED, $expanded);

        static::assertContains('integer', $expanded);
        static::assertContains('double', $expanded);
        static::assertContains('boolean', $expanded);
    }

    #[DataProvider('provideNormalize')]
    public function testNormalize(string $type, string $expected): void
    {
        static::assertSame($expected, Type::normalize($type));
    }

    #[DataProvider('provideNormalizeException')]
    public function testNormalizeException(string $type, string $message): void
    {
        static::expectException(NotAType::class);
        static::expectExceptionMessage($message);

        Type::normalize($type);
    }

    /** @param class-string $class */
    #[DataProvider('provideOfProperty')]
    public function testOfProperty(string $class, string $property, string $expected): void
    {
        static::assertSame(
            $expected,
            Type::ofProperty($class, $property),
        );
    }

    /** @param class-string $class */
    #[DataProvider('provideOfMethod')]
    public function testOfMethod(string $class, string $method, string $expected): void
    {
        static::assertSame(
            $expected,
            Type::ofMethod($class, $method),
        );
    }

    #[DataProvider('provideOfFunction')]
    public function testOfFunction(string|callable $function, string $expected): void
    {
        static::assertSame($expected, Type::ofFunction($function));
    }

    /** @param class-string $class */
    #[DataProvider('provideOfMethodParameter')]
    public function testOfMethodParameter(
        string $class,
        string $method,
        string $parameter,
        string $expected,
    ): void {
        static::assertSame(
            $expected,
            Type::ofMethodParameter($class, $method, $parameter),
        );
    }

    public function testOfMethodParameterException(): void
    {
        static::expectException(NoSuchParameter::class);
        static::expectExceptionMessage('No parameter "$nonexistent" found.');

        Type::ofMethodParameter(TypeStub::class, 'takesInt', 'nonexistent');
    }

    #[DataProvider('provideOfFunctionParameter')]
    public function testOfFunctionParameter(
        string|callable $function,
        string $parameter,
        string $expected,
    ): void {
        static::assertSame(
            $expected,
            Type::ofFunctionParameter($function, $parameter),
        );
    }

    public function testOfFunctionParameterException(): void
    {
        static::expectException(NoSuchParameter::class);
        static::expectExceptionMessage('No parameter "$nonexistent" found.');

        Type::ofFunctionParameter(static fn ($a) => $a, 'nonexistent');
    }

    /** @param class-string $type */
    #[DataProvider('provideFromReflectionType')]
    public function testFromReflectionType(string $type, string $method, string $expected): void
    {
        static::assertSame(
            $expected,
            Type::fromReflectionType(
                (new ReflectionClass($type))
                    ->getMethod($method)
                    ->getReturnType(),
            ),
        );
    }

    public function testFromReflectionTypeException(): void
    {
        $mock = $this->createStub(ReflectionType::class);

        static::expectException(UnsupportedReflectionType::class);
        static::expectExceptionMessage('Reflection type "' . $mock::class . '" not supported yet.');

        Type::fromReflectionType($mock);
    }

    #[DataProvider('provideUnion')]
    public function testUnion(string $expected, string $first, string ...$rest): void
    {
        static::assertSame($expected, Type::union($first, ...$rest));
    }

    #[DataProvider('provideUnionVoidThrows')]
    public function testUnionException(string $first, string ...$rest): void
    {
        static::expectException(InvalidArgumentException::class);

        Type::union($first, ...$rest);
    }

    #[DataProvider('provideIntersection')]
    public function testIntersection(string $expected, string $first, string ...$rest): void
    {
        static::assertSame($expected, Type::intersection($first, ...$rest));
    }

    #[DataProvider('provideIntersectionForbiddenScalars')]
    public function testIntersectionException(string $first, string ...$rest): void
    {
        static::expectException(InvalidArgumentException::class);

        Type::intersection($first, ...$rest);
    }

    /** @return array<array{0:string, 1:string}> */
    public static function provideNormalize(): array
    {
        return [
            ['integer', Type::INT],
            ['double', Type::FLOAT],
            ['boolean', Type::BOOL],
            ['NULL', Type::NULL],
            ['int', Type::INT],
            ['float', Type::FLOAT],
            ['string', Type::STRING],
            [TypeStub::class, TypeStub::class],
            [StubInterfaceA::class, StubInterfaceA::class],
            [StubInterfaceA::class . '|null', StubInterfaceA::class . '|null'],
            ['null|' . StubInterfaceA::class, StubInterfaceA::class . '|null'],
            ['integer|null', 'int|null'],
            ['null|null', 'null'],
        ];
    }

    /** @return array<array{0:string, 1:string}> */
    public static function provideNormalizeException(): array
    {
        return [
            ['NotAType', 'Expected a valid type. Got "NotAType".'],
            ['int|string', 'Expected a valid type. Got "int|string".'],
            ['int|string|null', 'Expected a valid type. Got "int|string|null".'],
            [
                StubInterfaceA::class . '&' . StubInterfaceB::class,
                'Expected a valid type. Got "' . StubInterfaceA::class . '&' . StubInterfaceB::class . '".',
            ],
            ['mixed|null', 'Expected a valid type. Got "mixed|null".'],
            ['void|null', 'Expected a valid type. Got "void|null".'],
            ['never|null', 'Expected a valid type. Got "never|null".'],
        ];
    }

    /** @return array<array{0:mixed, 1:string}> */
    public static function provideLiterals(): array
    {
        return [
            [true, 'true'],
            [false, 'false'],
            [null, 'null'],
            [[], 'array'],
            ['Hello World', '"Hello World"'],
            [new StubClass(), '"' . StubClass::class . '"'],
            [42, '42'],
            [static fn (mixed $a): mixed => $a, 'callable'],
        ];
    }

    /** @return array<array{0:mixed, 1:string}> */
    public static function provideStrings(): array
    {
        return [
            [true, 'bool'],
            [false, 'bool'],
            [42, 'int'],
            [0.99, 'float'],
            [[], 'array'],
            [null, 'null'],
            [new StubClass(), 'object'],
            [static fn (mixed $a): mixed => $a, 'callable'],
        ];
    }

    /** @return array<string, array<int, string>> */
    public static function provideUnion(): array
    {
        return [
            ['int|string', 'int', 'string'],
            ['int', 'int', 'int'],
            ['int', 'integer', 'int'],
            ['mixed', 'int', 'mixed'],
            ['int', 'int', 'never'],
            ['string', 'never', 'string'],
        ];
    }

    /** @return array<string, array<int, string>> */
    public static function provideUnionVoidThrows(): array
    {
        return [
            ['void', 'int'],
            ['int', 'void'],
        ];
    }

    /** @return array<string, array<int, string>> */
    public static function provideIntersection(): array
    {
        return [
            [
                StubInterfaceA::class . '&' . StubInterfaceB::class,
                StubInterfaceA::class,
                StubInterfaceB::class,
            ],
            [
                StubInterfaceA::class,
                StubInterfaceA::class,
                StubInterfaceA::class,
            ],
            [
                'never',
                StubInterfaceA::class,
                'never',
            ],
        ];
    }

    /** @return array<string, array<int, string>> */
    public static function provideIntersectionForbiddenScalars(): array
    {
        return [
            [StubInterfaceA::class, 'int'],
            [StubInterfaceA::class, 'string'],
            [StubInterfaceA::class, 'null'],
            [StubInterfaceA::class, 'mixed'],
            [StubInterfaceA::class, 'void'],
            [StubInterfaceA::class, 'integer'],
        ];
    }

    /** @return array<string, array{0:string, 1:string, 2:string}> */
    public static function provideOfProperty(): array
    {
        return [
            [TypeStub::class, 'intProp', Type::INT],
            [TypeStub::class, 'stringProp', Type::STRING],
            [TypeStub::class, 'nullableProp', Type::STRING . '|' . Type::NULL],
            [TypeStub::class, 'mixedProp', Type::MIXED],
            [TypeStub::class, 'untypedProp', Type::MIXED],
        ];
    }

    /** @return array<string, array{0:string, 1:string, 2:string}> */
    public static function provideOfMethod(): array
    {
        return [
            [TypeStub::class, 'returnsInt', Type::INT],
            [TypeStub::class, 'returnsString', Type::STRING],
            [TypeStub::class, 'returnsNullableString', Type::STRING . '|' . Type::NULL],
            [TypeStub::class, 'returnsUnion', Type::STRING . '|' . Type::INT],
            [TypeStub::class, 'returnsVoid', Type::VOID],
            [TypeStub::class, 'returnsMixed', Type::MIXED],
            [TypeStub::class, 'returnsNoType', Type::MIXED],
            [TypeStub::class, 'returnsIntersection', StubInterfaceA::class . '&' . StubInterfaceB::class],
        ];
    }

    /** @return array<string, array{0:string, 1:string, 2:string, 3:string}> */
    public static function provideOfMethodParameter(): array
    {
        return [
            [TypeStub::class, 'takesInt', 'value', Type::INT],
            [TypeStub::class, 'takesNullable', 'value', Type::STRING . '|' . Type::NULL],
            [TypeStub::class, 'takesUnion', 'value', Type::STRING . '|' . Type::INT],
            [TypeStub::class, 'takesUntyped', 'value', Type::MIXED],
        ];
    }

    /** @return array<string, array{0:string|callable, 1:string}> */
    public static function provideOfFunction(): array
    {
        return [
            [static fn (): int => 1, Type::INT],
            [static fn () => 1, Type::MIXED],
            ['strlen', Type::INT],
            [[TypeStub::class, 'returnsFloat'], Type::FLOAT],
            [[new TypeStub(), 'returnsString'], Type::STRING],
            [new TypeStub(), Type::INT],
        ];
    }

    /** @return array<string, array{0:string|callable, 1:string, 2:string}> */
    public static function provideOfFunctionParameter(): array
    {
        $nullableStringType = Type::STRING . '|' . Type::NULL;

        return [
            [static fn (int $count): int => $count + 1, 'count', Type::INT],
            [static fn (string|null $label) => $label, 'label', $nullableStringType],
            ['strlen', 'string', Type::STRING],
            [[TypeStub::class, 'takesFloat'], 'value', Type::FLOAT],
            [[new TypeStub(), 'takesNullable'], 'value', $nullableStringType],
        ];
    }

    /** @return array<string, array{0:class-string, 1:string, 2:string}> */
    public static function provideFromReflectionType(): array
    {
        return [
            [TypeStub::class, 'returnsInt', 'int'],
            [TypeStub::class, 'returnsVoid', 'void'],
            [TypeStub::class, 'returnsIntOrStringOrNull', Type::STRING . '|' . Type::INT . '|' . Type::NULL],
            [TypeStub::class, 'returnsMixed', Type::MIXED],
            [TypeStub::class, 'returnsNoType', Type::MIXED],
            [TypeStub::class, 'returnsIntersection', StubInterfaceA::class . '&' . StubInterfaceB::class],
            [TypeStub::class, 'returnsUnion', Type::STRING . '|' . Type::INT],
            [TypeStub::class, 'returnsNullableString', Type::STRING . '|' . Type::NULL],
        ];
    }
}
