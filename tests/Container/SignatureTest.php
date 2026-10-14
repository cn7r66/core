<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container;

use Closure;
use Generator;
use PHPUnit\Framework\TestCase;
use Throwable;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Container;
use Vivarium\Container\Exception\CannotBeOmitted;
use Vivarium\Container\Exception\CannotBeProjected;
use Vivarium\Container\Exception\CannotBeReplaced;
use Vivarium\Container\Parameter\Optional;
use Vivarium\Container\Parameter\Required;
use Vivarium\Container\Parameter\Variadic;
use Vivarium\Container\Provider;
use Vivarium\Container\Signature;
use Vivarium\Test\Container\Stub\StubBase;
use Vivarium\Test\Container\Stub\StubFactory;
use Vivarium\Test\Container\Stub\StubForVariadicShadowSource;
use Vivarium\Test\Container\Stub\StubForVariadicShadowTarget;
use Vivarium\Test\Container\Stub\StubInvokable;
use Vivarium\Test\Container\Stub\StubService;
use Vivarium\Test\Container\Stub\StubWithBaseTypedArg;
use Vivarium\Test\Container\Stub\StubWithFullSignature;
use Vivarium\Test\Container\Stub\StubWithMultipleArgs;
use Vivarium\Test\Container\Stub\StubWithNoArgs;
use Vivarium\Test\Container\Stub\StubWithNoConstructor;
use Vivarium\Test\Container\Stub\StubWithOptionalArg;
use Vivarium\Test\Container\Stub\StubWithOptionalArgNamedValue;
use Vivarium\Test\Container\Stub\StubWithRequiredStringArg;
use Vivarium\Test\Container\Stub\StubWithSubclassTypedArg;
use Vivarium\Test\Container\Stub\StubWithUnionTypedArg;
use Vivarium\Test\Container\Stub\StubWithValidFullSignature;
use Vivarium\Type\Exception\NotAType;

/** @coversDefaultClass \Vivarium\Container\Signature */
final class SignatureTest extends TestCase
{
    /**
     * @covers ::ofConstructor
     * @covers ::__construct
     * @covers ::fromReflector
     * @covers ::fromParameter
     */
    public function testOfConstructor(): void
    {
        $noConstructor = Signature::ofConstructor(StubWithNoConstructor::class);

        static::assertInstanceOf(Signature::class, $noConstructor);
        static::assertSame(0, $noConstructor->count());

        $full = Signature::ofConstructor(StubWithValidFullSignature::class);

        static::assertSame(3, $full->count());

        static::assertInstanceOf(Required::class, $full->getParameter(0));
        static::assertSame('service', $full->getParameter(0)->getName());
        static::assertSame(StubService::class, $full->getParameter(0)->getType());

        static::assertInstanceOf(Optional::class, $full->getParameter(1));
        static::assertSame('label', $full->getParameter(1)->getName());
        static::assertSame('string', $full->getParameter(1)->getType());

        static::assertInstanceOf(Variadic::class, $full->getParameter(2));
        static::assertSame('counts', $full->getParameter(2)->getName());
        static::assertSame('int', $full->getParameter(2)->getType());
    }

    /**
     * @covers ::ofConstructor
     * @covers ::__construct
     * @covers ::fromReflector
     * @covers ::fromParameter
     * @dataProvider provideOfConstructorExceptions
     */
    public function testOfConstructorException(string $class, string $exception): void
    {
        static::expectException($exception);

        Signature::ofConstructor($class);
    }

    /** @return Generator<string, array{string, string}> */
    public static function provideOfConstructorExceptions(): Generator
    {
        yield 'non-existent class' => ['Vivarium\\Test\\Container\\Stub\\NonExistentStub', AssertionFailed::class];
        yield 'union parameter type' => [StubWithUnionTypedArg::class, NotAType::class];
    }

    /**
     * @covers ::ofConstructor
     * @covers ::__construct
     * @covers ::fromReflector
     * @covers ::fromParameter
     */
    public function testOfConstructorWithNullableUnionParameter(): void
    {
        $signature = Signature::ofConstructor(StubWithFullSignature::class);

        static::assertSame(3, $signature->count());

        static::assertSame('service', $signature->getParameter(0)->getName());
        static::assertSame(StubService::class, $signature->getParameter(0)->getType());

        static::assertSame('name', $signature->getParameter(1)->getName());
        static::assertSame('string|null', $signature->getParameter(1)->getType());

        static::assertSame('tags', $signature->getParameter(2)->getName());
        static::assertSame('string', $signature->getParameter(2)->getType());
    }

    /**
     * @covers ::ofMethod
     * @covers ::__construct
     * @covers ::fromReflector
     * @covers ::fromParameter
     */
    public function testOfMethod(): void
    {
        $signature = Signature::ofMethod(StubFactory::class, 'create');

        static::assertSame(1, $signature->count());
        static::assertInstanceOf(Required::class, $signature->getParameter(0));
        static::assertSame('service', $signature->getParameter(0)->getName());
        static::assertSame(StubService::class, $signature->getParameter(0)->getType());
    }

    /**
     * @covers ::ofMethod
     * @covers ::__construct
     * @covers ::fromReflector
     * @covers ::fromParameter
     */
    public function testOfMethodException(): void
    {
        static::expectException(AssertionFailed::class);

        Signature::ofMethod(StubService::class, 'nonExistentMethod');
    }

    /**
     * @covers ::ofFunction
     * @covers ::__construct
     * @covers ::fromReflector
     * @covers ::fromParameter
     * @dataProvider provideOfFunction
     */
    public function testOfFunction(callable $fn, int $expectedCount): void
    {
        $signature = Signature::ofFunction($fn);

        static::assertSame($expectedCount, $signature->count());
    }

    /** @return Generator<string, array{callable, int}> */
    public static function provideOfFunction(): Generator
    {
        $closure = static function (string $name, int $count = 1): void {
        };

        yield 'closure' => [$closure, 2];
        yield 'named function' => ['strlen', 1];
    }

    /**
     * @covers ::ofFunction
     * @covers ::__construct
     * @covers ::fromReflector
     * @covers ::fromParameter
     * @dataProvider provideOfFunctionException
     */
    public function testOfFunctionException(callable $fn): void
    {
        static::expectException(AssertionFailed::class);

        Signature::ofFunction($fn);
    }

    /** @return Generator<string, array{callable}> */
    public static function provideOfFunctionException(): Generator
    {
        yield 'method-array callable' => [[new StubFactory(), 'create']];
        yield 'invokable object' => [new StubInvokable()];
    }

    /**
     * @covers ::count
     * @dataProvider provideCount
     */
    public function testCount(string $class, int $expected): void
    {
        static::assertSame($expected, Signature::ofConstructor($class)->count());
    }

    /** @return Generator<string, array{string, int}> */
    public static function provideCount(): Generator
    {
        yield 'no parameters' => [StubWithNoArgs::class, 0];
        yield 'three parameters' => [StubWithValidFullSignature::class, 3];
    }

    /**
     * @covers ::getParameter
     * @dataProvider provideGetParameter
     */
    public function testGetParameter(int $position, string $expectedName): void
    {
        $signature = Signature::ofConstructor(StubWithMultipleArgs::class);

        static::assertSame($expectedName, $signature->getParameter($position)->getName());
    }

    /** @return Generator<string, array{int, string}> */
    public static function provideGetParameter(): Generator
    {
        yield 'position 0' => [0, 'service'];
        yield 'position 1' => [1, 'name'];
        yield 'position 2' => [2, 'count'];
    }

    /**
     * @covers ::getParameter
     * @dataProvider provideGetParameterException
     */
    public function testGetParameterException(int $position): void
    {
        static::expectException(AssertionFailed::class);

        Signature::ofConstructor(StubWithMultipleArgs::class)->getParameter($position);
    }

    /** @return Generator<string, array{int}> */
    public static function provideGetParameterException(): Generator
    {
        yield 'negative position' => [-1];
        yield 'out of range position' => [3];
    }

    /**
     * @covers ::positionOf
     * @dataProvider providePositionOf
     */
    public function testPositionOf(string $name, int $expected): void
    {
        static::assertSame($expected, Signature::ofConstructor(StubWithMultipleArgs::class)->positionOf($name));
    }

    /** @return Generator<string, array{string, int}> */
    public static function providePositionOf(): Generator
    {
        yield 'service' => ['service', 0];
        yield 'name' => ['name', 1];
        yield 'count' => ['count', 2];
    }

    /** @covers ::positionOf */
    public function testPositionOfException(): void
    {
        static::expectException(AssertionFailed::class);

        Signature::ofConstructor(StubWithMultipleArgs::class)->positionOf('unknown');
    }

    /**
     * @covers ::hasArgument
     * @dataProvider provideHasArgument
     */
    public function testHasArgument(Signature $signature, int $position, bool $expected): void
    {
        static::assertSame($expected, $signature->hasArgument($position));
    }

    /** @return Generator<string, array{Signature, int, bool}> */
    public static function provideHasArgument(): Generator
    {
        $fresh = Signature::ofConstructor(StubWithMultipleArgs::class);

        yield 'fresh position 0' => [$fresh, 0, false];
        yield 'fresh position 1' => [$fresh, 1, false];
        yield 'fresh position 2' => [$fresh, 2, false];

        $provider = self::createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');

        $bound = $fresh->withArgument(1, $provider);

        yield 'bound position 0' => [$bound, 0, false];
        yield 'bound position 1' => [$bound, 1, true];
        yield 'bound position 2' => [$bound, 2, false];
    }

    /**
     * @covers ::hasArgument
     * @dataProvider provideHasArgumentException
     */
    public function testHasArgumentException(int $position): void
    {
        static::expectException(AssertionFailed::class);

        Signature::ofConstructor(StubWithMultipleArgs::class)->hasArgument($position);
    }

    /** @return Generator<string, array{int}> */
    public static function provideHasArgumentException(): Generator
    {
        yield 'negative position' => [-1];
        yield 'out of range position' => [3];
    }

    /**
     * @covers ::withArgument
     * @dataProvider provideWithArgument
     */
    public function testWithArgument(
        Signature $signature,
        int $position,
        Provider $provider,
        bool $originalHasArgument,
    ): void {
        $modified = $signature->withArgument($position, $provider);

        static::assertNotSame($signature, $modified);
        static::assertSame($originalHasArgument, $signature->hasArgument($position));
        static::assertTrue($modified->hasArgument($position));
        static::assertSame($provider, $modified->getArguments()[$position]);
    }

    /** @return Generator<string, array{Signature, int, Provider, bool}> */
    public static function provideWithArgument(): Generator
    {
        $stringProvider = self::createStub(Provider::class);
        $stringProvider->method('getTarget')->willReturn('string');

        yield 'required scalar position' => [
            Signature::ofConstructor(StubWithRequiredStringArg::class),
            0,
            $stringProvider,
            false,
        ];

        $arrayProvider = self::createStub(Provider::class);
        $arrayProvider->method('getTarget')->willReturn('array');

        yield 'variadic position bound with array-targeting provider' => [
            Signature::ofConstructor(StubForVariadicShadowTarget::class),
            0,
            $arrayProvider,
            false,
        ];

        $firstProvider = self::createStub(Provider::class);
        $firstProvider->method('getTarget')->willReturn('string');

        $secondProvider = self::createStub(Provider::class);
        $secondProvider->method('getTarget')->willReturn('string');

        $alreadyBound = Signature::ofConstructor(StubWithRequiredStringArg::class)
            ->withArgument(0, $firstProvider);

        yield 'rebind replaces previous provider' => [$alreadyBound, 0, $secondProvider, true];
    }

    /**
     * @covers ::withArgument
     * @dataProvider provideWithArgumentException
     */
    public function testWithArgumentException(Signature $signature, int $position, Provider $provider): void
    {
        static::expectException(AssertionFailed::class);

        $signature->withArgument($position, $provider);
    }

    /** @return Generator<string, array{Signature, int, Provider}> */
    public static function provideWithArgumentException(): Generator
    {
        $stringProvider = self::createStub(Provider::class);
        $stringProvider->method('getTarget')->willReturn('string');

        $signature = Signature::ofConstructor(StubWithRequiredStringArg::class);

        yield 'negative position' => [$signature, -1, $stringProvider];
        yield 'out of range position' => [$signature, $signature->count(), $stringProvider];

        $intProvider = self::createStub(Provider::class);
        $intProvider->method('getTarget')->willReturn('int');

        yield 'mismatched target type' => [$signature, 0, $intProvider];

        $variadicSignature = Signature::ofConstructor(StubForVariadicShadowTarget::class);

        yield 'variadic position bound with element-type provider' => [$variadicSignature, 0, $stringProvider];
    }

    /** @covers ::getArguments */
    public function testGetArguments(): void
    {
        $signature = Signature::ofConstructor(StubWithValidFullSignature::class);
        $arguments = $signature->getArguments();

        static::assertCount(3, $arguments);
        static::assertArrayHasKey(0, $arguments);
        static::assertArrayHasKey(1, $arguments);
        static::assertArrayHasKey(2, $arguments);

        $container = $this->createMock(Container::class);

        static::assertSame('default', $arguments[1]->provide($container));
        static::assertSame('array', $arguments[2]->getTarget());
        static::assertSame([], $arguments[2]->provide($container));

        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('string');

        $bound = $signature->withArgument(1, $provider);

        static::assertSame($provider, $bound->getArguments()[1]);
    }

    /**
     * @covers ::project
     * @dataProvider provideProject
     */
    public function testProject(Signature $source, Signature $target, Closure $assert): void
    {
        $assert($source->project($target));
    }

    /** @return Generator<string, array{Signature, Signature, Closure}> */
    public static function provideProject(): Generator
    {
        $sourceProvider = self::createStub(Provider::class);
        $sourceProvider->method('getTarget')->willReturn('string');

        $targetProvider = self::createStub(Provider::class);
        $targetProvider->method('getTarget')->willReturn('string');

        $sourceBound = Signature::ofConstructor(StubWithRequiredStringArg::class)
            ->withArgument(0, $sourceProvider);
        $targetBound = Signature::ofConstructor(StubWithOptionalArg::class)
            ->withArgument(0, $targetProvider);

        yield 'target explicit binding wins over source' => [
            $sourceBound,
            $targetBound,
            static function (Signature $projected) use ($targetProvider): void {
                static::assertTrue($projected->hasArgument(0));
                static::assertSame($targetProvider, $projected->getArguments()[0]);
            },
        ];

        $unboundTarget = Signature::ofConstructor(StubWithOptionalArg::class);

        yield 'source explicit binding transplants when target has none' => [
            $sourceBound,
            $unboundTarget,
            static function (Signature $projected) use ($sourceProvider): void {
                static::assertTrue($projected->hasArgument(0));
                static::assertSame($sourceProvider, $projected->getArguments()[0]);
            },
        ];

        yield 'neither explicit binding leaves the target default standing' => [
            Signature::ofConstructor(StubWithRequiredStringArg::class),
            Signature::ofConstructor(StubWithOptionalArg::class),
            static function (Signature $projected): void {
                static::assertFalse($projected->hasArgument(0));
                static::assertSame(
                    'default',
                    $projected->getArguments()[0]->provide(self::createStub(Container::class)),
                );
            },
        ];

        yield 'result names resolve through the target' => [
            Signature::ofConstructor(StubWithRequiredStringArg::class),
            Signature::ofConstructor(StubWithOptionalArgNamedValue::class),
            static function (Signature $projected): void {
                static::assertSame(0, $projected->positionOf('value'));
            },
        ];

        yield 'a class-typed target may widen from a subclass-typed source' => [
            Signature::ofConstructor(StubWithSubclassTypedArg::class),
            Signature::ofConstructor(StubWithBaseTypedArg::class),
            static function (Signature $projected): void {
                static::assertSame(1, $projected->count());
                static::assertSame(StubBase::class, $projected->getParameter(0)->getType());
            },
        ];

        yield 'a variadic target may shadow an optional source' => [
            Signature::ofConstructor(StubForVariadicShadowSource::class),
            Signature::ofConstructor(StubForVariadicShadowTarget::class),
            static function (Signature $projected): void {
                static::assertSame(1, $projected->count());
                static::assertFalse($projected->hasArgument(0));
                static::assertSame('array', $projected->getArguments()[0]->getTarget());
            },
        ];

        yield 'an extra optional target position beyond source is omittable' => [
            Signature::ofConstructor(StubWithNoArgs::class),
            Signature::ofConstructor(StubWithOptionalArg::class),
            static function (Signature $projected): void {
                static::assertSame(1, $projected->count());
                static::assertFalse($projected->hasArgument(0));
            },
        ];

        yield 'an extra variadic target position beyond source is omittable' => [
            Signature::ofConstructor(StubWithNoArgs::class),
            Signature::ofConstructor(StubForVariadicShadowTarget::class),
            static function (Signature $projected): void {
                static::assertSame(1, $projected->count());
            },
        ];
    }

    /**
     * @param class-string<Throwable> $exception
     *
     * @covers ::project
     * @dataProvider provideProjectException
     */
    public function testProjectException(Signature $source, Signature $target, string $exception): void
    {
        static::expectException($exception);

        $source->project($target);
    }

    /** @return Generator<string, array{Signature, Signature, class-string<Throwable>}> */
    public static function provideProjectException(): Generator
    {
        yield 'target has fewer positions than source' => [
            Signature::ofConstructor(StubWithRequiredStringArg::class),
            Signature::ofConstructor(StubWithNoArgs::class),
            CannotBeProjected::class,
        ];

        yield 'an extra required target position beyond source is not omittable' => [
            Signature::ofConstructor(StubWithNoArgs::class),
            Signature::ofConstructor(StubWithRequiredStringArg::class),
            CannotBeOmitted::class,
        ];

        yield 'a required target cannot replace an optional source' => [
            Signature::ofConstructor(StubWithOptionalArg::class),
            Signature::ofConstructor(StubWithRequiredStringArg::class),
            CannotBeReplaced::class,
        ];

        yield 'a subclass-typed target cannot replace a base-typed source' => [
            Signature::ofConstructor(StubWithBaseTypedArg::class),
            Signature::ofConstructor(StubWithSubclassTypedArg::class),
            CannotBeReplaced::class,
        ];

        $mismatchedProvider = self::createStub(Provider::class);
        $mismatchedProvider->method('getTarget')->willReturn('string');

        yield 'a transplanted source binding must still fit the target variadic slot' => [
            Signature::ofConstructor(StubWithRequiredStringArg::class)->withArgument(0, $mismatchedProvider),
            Signature::ofConstructor(StubForVariadicShadowTarget::class),
            AssertionFailed::class,
        ];
    }
}
