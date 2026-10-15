<?php

declare(strict_types=1);

namespace Vivarium\Test\Container\Provider;

use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Provider;
use Vivarium\Container\Provider\Fun;
use Vivarium\Test\Container\Stub\StubService;

/** @coversDefaultClass \Vivarium\Container\Provider\Fun */
final class FunTest extends TestCase
{
    /** @covers ::__construct */
    public function testConstructWithValidFunction(): void
    {
        static::expectNotToPerformAssertions();

        new Fun(static fn (Container $container): string => 'test');
    }

    /** @covers ::__construct */
    public function testConstructWithNonCallableThrows(): void
    {
        static::expectException(AssertionFailed::class);

        new Fun('nonexistent_function');
    }

    /**
     * @dataProvider provideFunctionsAndTheirTargets
     * @covers ::getTarget
     */
    public function testGetTargetReturnsReturnType(callable $function, string $expected): void
    {
        static::assertSame($expected, (new Fun($function))->getTarget());
    }

    /** @return array<string, array{callable, string}> */
    public static function provideFunctionsAndTheirTargets(): array
    {
        return [
            'declared return type' => [static fn (Container $container): string => 'hello', 'string'],
            'no declared return type' => [static fn (Container $container) => 'hello', 'mixed'],
        ];
    }

    /** @covers ::getCapabilities */
    public function testGetCapabilitiesReturnsInjectableInterceptableAndDecorable(): void
    {
        $provider     = new Fun(static fn (Container $container): string => 'test');
        $capabilities = $provider->getCapabilities();

        static::assertTrue($capabilities->contains(Capability::INJECTABLE));
        static::assertTrue($capabilities->contains(Capability::INTERCEPTABLE));
        static::assertTrue($capabilities->contains(Capability::DECORABLE));
        static::assertCount(3, $capabilities);
    }

    /** @covers ::provide */
    public function testProvideInvokesZeroArgumentClosureWithoutConsultingContainer(): void
    {
        $container = $this->createMock(Container::class);
        $container->expects(static::never())->method('get');
        $container->expects(static::never())->method('has');

        $result = (new Fun(static fn (): string => 'value'))->provide($container);

        static::assertSame('value', $result);
    }

    /** @covers ::provide */
    public function testProvideAutowiresRequiredTypedParameter(): void
    {
        $service   = new StubService();
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($service);

        $result = (new Fun(static fn (StubService $service): StubService => $service))->provide($container);

        static::assertSame($service, $result);
    }

    /** @covers ::provide */
    public function testProvideUsesContainerValueForOptionalParameter(): void
    {
        $container = $this->createMock(Container::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn('custom');

        $result = (new Fun(static fn (string $name = 'default'): string => $name))->provide($container);

        static::assertSame('custom', $result);
    }

    /** @covers ::provide */
    public function testProvideUsesDefaultWhenOptionalParameterNotInContainer(): void
    {
        $container = $this->createMock(Container::class);
        $container->method('has')->willReturn(false);
        $container->expects(static::never())->method('get');

        $result = (new Fun(static fn (string $name = 'default'): string => $name))->provide($container);

        static::assertSame('default', $result);
    }

    /**
     * @covers ::provide
     * @covers ::bindArgument
     */
    public function testProvidePrefersExplicitlyBoundArgumentOverAutoResolution(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('string');
        $provider->method('provide')->willReturn('bound');

        $container = $this->createMock(Container::class);
        $container->expects(static::never())->method('get');

        $fun = (new Fun(static fn (string $name = 'default'): string => $name))
            ->bindArgument('name')
            ->toProvider($provider);

        static::assertSame('bound', $fun->provide($container));
    }

    /** @covers ::bindArgument */
    public function testBindArgument(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('string');
        $provider->method('provide')->willReturn('bound');

        $original = new Fun(static fn (string $name = 'default'): string => $name);
        $bound    = $original->bindArgument('name')->toProvider($provider);

        static::assertNotSame($original, $bound);

        $container = $this->createMock(Container::class);
        $container->method('has')->willReturn(false);

        static::assertSame('bound', $bound->provide($container));
        static::assertSame('default', $original->provide($container));
    }

    /** @covers ::bindArgumentAtPosition */
    public function testBindArgumentAtPosition(): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->method('getTarget')->willReturn('array');
        $provider->method('provide')->willReturn(['a', 'b', 'c']);

        $fun = (new Fun(static fn (string ...$names): array => $names))
            ->bindArgumentAtPosition(0)
            ->toProvider($provider);

        $container = $this->createMock(Container::class);

        static::assertSame(['a', 'b', 'c'], $fun->provide($container));
    }

    /**
     * @dataProvider provideEqualFunPairs
     * @covers ::equals
     */
    public function testEqualsReturnsTrueForEqualFuns(Fun $first, Fun $second): void
    {
        static::assertTrue($first->equals($second));
    }

    /**
     * @dataProvider provideUnequalPairs
     * @covers ::equals
     */
    public function testEqualsReturnsFalseForUnequalObjects(Fun $first, object $second): void
    {
        static::assertFalse($first->equals($second));
    }

    /**
     * @dataProvider provideEqualFunPairs
     * @covers ::hash
     */
    public function testHashIsSameForEqualFuns(Fun $first, Fun $second): void
    {
        static::assertSame($first->hash(), $second->hash());
    }

    /** @return array<string, array{Fun, Fun}> */
    public static function provideEqualFunPairs(): array
    {
        $shared   = static fn (): string => 'shared';
        $instance = new Fun(static fn (): string => 'x');

        $provider = self::createStub(Provider::class);
        $provider->method('getTarget')->willReturn('string');
        $provider->method('provide')->willReturn('bound');

        $withoutBinding = new Fun(static fn (string $name = 'default'): string => $name);
        $withBinding    = $withoutBinding->bindArgument('name')->toProvider($provider);

        return [
            'same closure instance' => [new Fun($shared), new Fun($shared)],
            'same instance' => [$instance, $instance],
            'same named function' => [new Fun('strlen'), new Fun('strlen')],
            'argument bindings excluded from identity' => [$withoutBinding, $withBinding],
        ];
    }

    /** @return array<string, array{Fun, object}> */
    public static function provideUnequalPairs(): array
    {
        return [
            'distinct closures with identical bodies' => [
                new Fun(static fn (): string => 'a'),
                new Fun(static fn (): string => 'a'),
            ],
            'different named function' => [new Fun('strlen'), new Fun('trim')],
            'different class' => [new Fun(static fn (): string => 'shared'), new stdClass()],
        ];
    }
}
