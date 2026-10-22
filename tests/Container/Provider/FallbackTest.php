<?php

declare(strict_types=1);

namespace Vivarium\Test\Container\Provider;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Container\Binding;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Provider;
use Vivarium\Container\Provider\Fallback;
use Vivarium\Test\Assertion\Stub\StubClass;

#[CoversClass(Fallback::class)]
final class FallbackTest extends TestCase
{
    public function testProvideReturnsPrimaryWhenAvailable(): void
    {
        $primary   = new Binding(StubClass::class, 'primary');
        $secondary = $this->createMock(Provider::class);
        $provider  = new Fallback($primary, $secondary);

        $primaryInstance = new StubClass();
        $container       = $this->createMock(Container::class);

        $container->expects(static::once())
            ->method('has')
            ->with($primary)
            ->willReturn(true);

        $container->expects(static::once())
            ->method('get')
            ->with($primary)
            ->willReturn($primaryInstance);

        $secondary->expects(static::never())
            ->method('provide');

        static::assertSame($primaryInstance, $provider->provide($container));
    }

    public function testProvideReturnsSecondaryWhenPrimaryUnavailable(): void
    {
        $primary   = new Binding(StubClass::class, 'primary');
        $secondary = $this->createMock(Provider::class);
        $provider  = new Fallback($primary, $secondary);

        $secondaryInstance = new StubClass();
        $container         = $this->createMock(Container::class);

        $container->expects(static::once())
            ->method('has')
            ->with($primary)
            ->willReturn(false);

        $container->expects(static::never())
            ->method('get');

        $secondary->expects(static::once())
            ->method('provide')
            ->with($container)
            ->willReturn($secondaryInstance);

        static::assertSame($secondaryInstance, $provider->provide($container));
    }

    public function testGetTargetReturnsUnionOfBothTypes(): void
    {
        $primary   = new Binding('string');
        $secondary = $this->createStub(Provider::class);
        $secondary->method('getTarget')->willReturn('int');
        $provider = new Fallback($primary, $secondary);

        static::assertSame('string|int', $provider->getTarget());
    }

    public function testGetCapabilitiesReturnsInterceptableAndDecorableButNotInjectable(): void
    {
        $primary      = new Binding(StubClass::class);
        $secondary    = $this->createStub(Provider::class);
        $provider     = new Fallback($primary, $secondary);
        $capabilities = $provider->getCapabilities();

        static::assertFalse($capabilities->contains(Capability::INJECTABLE));
        static::assertTrue($capabilities->contains(Capability::INTERCEPTABLE));
        static::assertTrue($capabilities->contains(Capability::DECORABLE));
        static::assertCount(2, $capabilities);
    }
}
