<?php

declare(strict_types=1);

namespace Vivarium\Test\Container\Provider;

use PHPUnit\Framework\TestCase;
use Vivarium\Container\Binding;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Provider\ContainerCall;
use Vivarium\Test\Assertion\Stub\StubClass;

/** @coversDefaultClass \Vivarium\Container\Provider\ContainerCall */
final class ContainerCallTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::provide
     */
    public function testProvideDelegatesToContainer(): void
    {
        $binding  = new Binding(StubClass::class);
        $provider = new ContainerCall($binding);

        $expectedInstance = new StubClass();
        $container        = $this->createMock(Container::class);

        $container->expects(static::once())
            ->method('get')
            ->with($binding)
            ->willReturn($expectedInstance);

        static::assertSame($expectedInstance, $provider->provide($container));
    }

    /** @covers ::getTarget */
    public function testGetTargetReturnsDelegatedBindingType(): void
    {
        $binding  = new Binding(StubClass::class);
        $provider = new ContainerCall($binding);

        static::assertSame(StubClass::class, $provider->getTarget());
    }

    /** @covers ::getCapabilities */
    public function testGetCapabilitiesReturnsInterceptableAndDecorableButNotInjectable(): void
    {
        $binding      = new Binding(StubClass::class);
        $provider     = new ContainerCall($binding);
        $capabilities = $provider->getCapabilities();

        static::assertFalse($capabilities->contains(Capability::INJECTABLE));
        static::assertTrue($capabilities->contains(Capability::INTERCEPTABLE));
        static::assertTrue($capabilities->contains(Capability::DECORABLE));
        static::assertCount(2, $capabilities);
    }
}
