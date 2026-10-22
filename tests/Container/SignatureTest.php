<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

#[CoversClass(Signature::class)]
final class SignatureTest extends TestCase
{
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

    #[DataProvider('provideUnsupportedClasses')]
    public function testOfConstructorException(string $class, string $exception): void
    {
        static::expectException($exception);

        Signature::ofConstructor($class);
    }

    /** @return array<string, array{string, string}> */
    public static function provideUnsupportedClasses(): array
    {
        return [
            'non-existent class' => ['Vivarium\\Test\\Container\\Stub\\NonExistentStub', AssertionFailed::class],
            'union parameter type' => [StubWithUnionTypedArg::class, NotAType::class],
        ];
    }

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

    public function testOfMethod(): void
    {
        $signature = Signature::ofMethod(StubFactory::class, 'create');

        static::assertSame(1, $signature->count());
        static::assertInstanceOf(Required::class, $signature->getParameter(0));
        static::assertSame('service', $signature->getParameter(0)->getName());
        static::assertSame(StubService::class, $signature->getParameter(0)->getType());
    }

    public function testOfMethodException(): void
    {
        static::expectException(AssertionFailed::class);

        Signature::ofMethod(StubService::class, 'nonExistentMethod');
    }

    #[DataProvider('provideFunctionsWithParameterCount')]
    public function testOfFunction(callable $fn, int $expectedCount): void
    {
        $signature = Signature::ofFunction($fn);

        static::assertSame($expectedCount, $signature->count());
    }

    /** @return array<string, array{callable, int}> */
    public static function provideFunctionsWithParameterCount(): array
    {
        $closure = static function (string $name, int $count = 1): void {
        };

        return [
            'closure' => [$closure, 2],
            'named function' => ['strlen', 1],
        ];
    }

    #[DataProvider('provideNonFunctionCallables')]
    public function testOfFunctionException(callable $fn): void
    {
        static::expectException(AssertionFailed::class);

        Signature::ofFunction($fn);
    }

    /** @return array<string, array{callable}> */
    public static function provideNonFunctionCallables(): array
    {
        return [
            'method-array callable' => [[new StubFactory(), 'create']],
            'invokable object' => [new StubInvokable()],
        ];
    }

    #[DataProvider('provideClassesWithParameterCount')]
    public function testCount(string $class, int $expected): void
    {
        static::assertSame($expected, Signature::ofConstructor($class)->count());
    }

    /** @return array<string, array{string, int}> */
    public static function provideClassesWithParameterCount(): array
    {
        return [
            'no parameters' => [StubWithNoArgs::class, 0],
            'three parameters' => [StubWithValidFullSignature::class, 3],
        ];
    }

    #[DataProvider('provideMultipleArgsPositionsAndNames')]
    public function testGetParameter(int $position, string $name): void
    {
        $signature = Signature::ofConstructor(StubWithMultipleArgs::class);

        static::assertSame($name, $signature->getParameter($position)->getName());
    }

    /**
     * The parameters of StubWithMultipleArgs: position and the name declared there.
     *
     * @return array<string, array{int, string}>
     */
    public static function provideMultipleArgsPositionsAndNames(): array
    {
        return [
            'service at position 0' => [0, 'service'],
            'name at position 1' => [1, 'name'],
            'count at position 2' => [2, 'count'],
        ];
    }

    #[DataProvider('provideInvalidPositions')]
    public function testGetParameterException(int $position): void
    {
        static::expectException(AssertionFailed::class);

        Signature::ofConstructor(StubWithMultipleArgs::class)->getParameter($position);
    }

    /**
     * Positions outside StubWithMultipleArgs' three parameters.
     *
     * @return array<string, array{int}>
     */
    public static function provideInvalidPositions(): array
    {
        return [
            'negative position' => [-1],
            'out of range position' => [3],
        ];
    }

    #[DataProvider('provideMultipleArgsPositionsAndNames')]
    public function testPositionOf(int $position, string $name): void
    {
        static::assertSame($position, Signature::ofConstructor(StubWithMultipleArgs::class)->positionOf($name));
    }

    public function testPositionOfException(): void
    {
        static::expectException(AssertionFailed::class);

        Signature::ofConstructor(StubWithMultipleArgs::class)->positionOf('unknown');
    }

    #[DataProvider('provideFreshAndBoundPositions')]
    public function testHasArgument(Signature $signature, int $position, bool $expected): void
    {
        static::assertSame($expected, $signature->hasArgument($position));
    }

    /** @return array<string, array{Signature, int, bool}> */
    public static function provideFreshAndBoundPositions(): array
    {
        $fresh = Signature::ofConstructor(StubWithMultipleArgs::class);

        $provider = self::createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');

        $bound = $fresh->withArgument(1, $provider);

        return [
            'fresh position 0' => [$fresh, 0, false],
            'fresh position 1' => [$fresh, 1, false],
            'fresh position 2' => [$fresh, 2, false],
            'bound position 0' => [$bound, 0, false],
            'bound position 1' => [$bound, 1, true],
            'bound position 2' => [$bound, 2, false],
        ];
    }

    #[DataProvider('provideInvalidPositions')]
    public function testHasArgumentException(int $position): void
    {
        static::expectException(AssertionFailed::class);

        Signature::ofConstructor(StubWithMultipleArgs::class)->hasArgument($position);
    }

    #[DataProvider('provideAcceptedBindings')]
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

    /** @return array<string, array{Signature, int, Provider, bool}> */
    public static function provideAcceptedBindings(): array
    {
        $stringProvider = self::createStub(Provider::class);
        $stringProvider->method('getTarget')->willReturn('string');

        $arrayProvider = self::createStub(Provider::class);
        $arrayProvider->method('getTarget')->willReturn('array');

        $firstProvider = self::createStub(Provider::class);
        $firstProvider->method('getTarget')->willReturn('string');

        $secondProvider = self::createStub(Provider::class);
        $secondProvider->method('getTarget')->willReturn('string');

        $alreadyBound = Signature::ofConstructor(StubWithRequiredStringArg::class)
            ->withArgument(0, $firstProvider);

        return [
            'required scalar position' => [
                Signature::ofConstructor(StubWithRequiredStringArg::class),
                0,
                $stringProvider,
                false,
            ],
            'variadic position bound with array-targeting provider' => [
                Signature::ofConstructor(StubForVariadicShadowTarget::class),
                0,
                $arrayProvider,
                false,
            ],
            'rebind replaces previous provider' => [$alreadyBound, 0, $secondProvider, true],
        ];
    }

    #[DataProvider('provideRejectedBindings')]
    public function testWithArgumentException(Signature $signature, int $position, Provider $provider): void
    {
        static::expectException(AssertionFailed::class);

        $signature->withArgument($position, $provider);
    }

    /** @return array<string, array{Signature, int, Provider}> */
    public static function provideRejectedBindings(): array
    {
        $stringProvider = self::createStub(Provider::class);
        $stringProvider->method('getTarget')->willReturn('string');

        $signature = Signature::ofConstructor(StubWithRequiredStringArg::class);

        $intProvider = self::createStub(Provider::class);
        $intProvider->method('getTarget')->willReturn('int');

        $variadicSignature = Signature::ofConstructor(StubForVariadicShadowTarget::class);

        return [
            'negative position' => [$signature, -1, $stringProvider],
            'out of range position' => [$signature, $signature->count(), $stringProvider],
            'mismatched target type' => [$signature, 0, $intProvider],
            'variadic position bound with element-type provider' => [$variadicSignature, 0, $stringProvider],
        ];
    }

    public function testGetArguments(): void
    {
        $signature = Signature::ofConstructor(StubWithValidFullSignature::class);
        $arguments = $signature->getArguments();

        static::assertCount(3, $arguments);
        static::assertArrayHasKey(0, $arguments);
        static::assertArrayHasKey(1, $arguments);
        static::assertArrayHasKey(2, $arguments);

        $container = $this->createStub(Container::class);

        static::assertSame('default', $arguments[1]->provide($container));
        static::assertSame('array', $arguments[2]->getTarget());
        static::assertSame([], $arguments[2]->provide($container));

        $provider = $this->createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');

        $bound = $signature->withArgument(1, $provider);

        static::assertSame($provider, $bound->getArguments()[1]);
    }

    #[DataProvider('provideExplicitBindingProjections')]
    public function testProjectKeepsTheWinningExplicitBinding(
        Signature $source,
        Signature $target,
        Provider $expected,
    ): void {
        $projected = $source->project($target);

        static::assertTrue($projected->hasArgument(0));
        static::assertSame($expected, $projected->getArguments()[0]);
    }

    /** @return array<string, array{Signature, Signature, Provider}> */
    public static function provideExplicitBindingProjections(): array
    {
        $sourceProvider = self::createStub(Provider::class);
        $sourceProvider->method('getTarget')->willReturn('string');

        $targetProvider = self::createStub(Provider::class);
        $targetProvider->method('getTarget')->willReturn('string');

        $sourceBound = Signature::ofConstructor(StubWithRequiredStringArg::class)
            ->withArgument(0, $sourceProvider);
        $targetBound = Signature::ofConstructor(StubWithOptionalArg::class)
            ->withArgument(0, $targetProvider);

        return [
            'target explicit binding wins over source' => [$sourceBound, $targetBound, $targetProvider],
            'source explicit binding transplants when target has none' => [
                $sourceBound,
                Signature::ofConstructor(StubWithOptionalArg::class),
                $sourceProvider,
            ],
        ];
    }

    public function testProjectLeavesTheTargetDefaultWhenNeitherIsBound(): void
    {
        $projected = Signature::ofConstructor(StubWithRequiredStringArg::class)
            ->project(Signature::ofConstructor(StubWithOptionalArg::class));

        static::assertFalse($projected->hasArgument(0));
        static::assertSame(
            'default',
            $projected->getArguments()[0]->provide(self::createStub(Container::class)),
        );
    }

    public function testProjectResolvesNamesThroughTheTarget(): void
    {
        $projected = Signature::ofConstructor(StubWithRequiredStringArg::class)
            ->project(Signature::ofConstructor(StubWithOptionalArgNamedValue::class));

        static::assertSame(0, $projected->positionOf('value'));
    }

    public function testProjectLetsAClassTypedTargetWidenFromASubclassTypedSource(): void
    {
        $projected = Signature::ofConstructor(StubWithSubclassTypedArg::class)
            ->project(Signature::ofConstructor(StubWithBaseTypedArg::class));

        static::assertSame(1, $projected->count());
        static::assertSame(StubBase::class, $projected->getParameter(0)->getType());
    }

    public function testProjectLetsAVariadicTargetShadowAnOptionalSource(): void
    {
        $projected = Signature::ofConstructor(StubForVariadicShadowSource::class)
            ->project(Signature::ofConstructor(StubForVariadicShadowTarget::class));

        static::assertSame(1, $projected->count());
        static::assertFalse($projected->hasArgument(0));
        static::assertSame('array', $projected->getArguments()[0]->getTarget());
    }

    public function testProjectOmitsAnExtraOptionalTargetPosition(): void
    {
        $projected = Signature::ofConstructor(StubWithNoArgs::class)
            ->project(Signature::ofConstructor(StubWithOptionalArg::class));

        static::assertSame(1, $projected->count());
        static::assertFalse($projected->hasArgument(0));
    }

    public function testProjectOmitsAnExtraVariadicTargetPosition(): void
    {
        $projected = Signature::ofConstructor(StubWithNoArgs::class)
            ->project(Signature::ofConstructor(StubForVariadicShadowTarget::class));

        static::assertSame(1, $projected->count());
    }

    /** @param class-string<Throwable> $exception */
    #[DataProvider('provideIncompatibleProjections')]
    public function testProjectException(Signature $source, Signature $target, string $exception): void
    {
        static::expectException($exception);

        $source->project($target);
    }

    /** @return array<string, array{Signature, Signature, class-string<Throwable>}> */
    public static function provideIncompatibleProjections(): array
    {
        $mismatchedProvider = self::createStub(Provider::class);
        $mismatchedProvider->method('getTarget')->willReturn('string');

        return [
            'target has fewer positions than source' => [
                Signature::ofConstructor(StubWithRequiredStringArg::class),
                Signature::ofConstructor(StubWithNoArgs::class),
                CannotBeProjected::class,
            ],
            'an extra required target position beyond source is not omittable' => [
                Signature::ofConstructor(StubWithNoArgs::class),
                Signature::ofConstructor(StubWithRequiredStringArg::class),
                CannotBeOmitted::class,
            ],
            'a required target cannot replace an optional source' => [
                Signature::ofConstructor(StubWithOptionalArg::class),
                Signature::ofConstructor(StubWithRequiredStringArg::class),
                CannotBeReplaced::class,
            ],
            'a subclass-typed target cannot replace a base-typed source' => [
                Signature::ofConstructor(StubWithBaseTypedArg::class),
                Signature::ofConstructor(StubWithSubclassTypedArg::class),
                CannotBeReplaced::class,
            ],
            'a transplanted source binding must still fit the target variadic slot' => [
                Signature::ofConstructor(StubWithRequiredStringArg::class)->withArgument(0, $mismatchedProvider),
                Signature::ofConstructor(StubForVariadicShadowTarget::class),
                AssertionFailed::class,
            ],
        ];
    }
}
