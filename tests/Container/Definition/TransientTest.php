<?php

declare(strict_types=1);

namespace Vivarium\Test\Container\Definition;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Container\Container;
use Vivarium\Container\Definition\Transient;
use Vivarium\Container\Enhancement;
use Vivarium\Container\Provider;
use Vivarium\Test\Assertion\Stub\StubClass;

#[CoversClass(Transient::class)]
final class TransientTest extends TestCase
{
    public function testSolveReturnsProviderResult(): void
    {
        $instance  = new StubClass();
        $provider  = $this->createMock(Provider::class);
        $container = $this->createStub(Container::class);

        $provider->expects(static::once())
            ->method('provide')
            ->with($container)
            ->willReturn($instance);

        $definition = new Transient($provider);

        static::assertSame($instance, $definition->solve($container));
    }

    public function testSolveCreatesNewInstanceEachTime(): void
    {
        $provider  = $this->createMock(Provider::class);
        $container = $this->createStub(Container::class);

        $provider->expects(static::exactly(2))
            ->method('provide')
            ->with($container)
            ->willReturnCallback(static fn (): StubClass => new StubClass());

        $definition = new Transient($provider);

        $instance1 = $definition->solve($container);
        $instance2 = $definition->solve($container);

        static::assertNotSame($instance1, $instance2);
    }

    public function testWithEnhancementReturnsNewInstance(): void
    {
        $provider    = $this->createStub(Provider::class);
        $enhancement = $this->createStub(Enhancement::class);

        $definition1 = new Transient($provider);
        $definition2 = $definition1->withEnhancement($enhancement, 100);

        static::assertNotSame($definition1, $definition2);
    }

    public function testSolveAppliesEnhancementsInPriorityOrder(): void
    {
        $instance  = new StubClass();
        $provider  = $this->createStub(Provider::class);
        $container = $this->createStub(Container::class);
        $enhanced1 = new StubClass();
        $enhanced2 = new StubClass();

        $provider->method('provide')
            ->willReturn($instance);

        $enhancement1 = $this->createMock(Enhancement::class);
        $enhancement2 = $this->createMock(Enhancement::class);

        $enhancement1->expects(static::once())
            ->method('enhance')
            ->with($instance, $container)
            ->willReturn($enhanced1);

        $enhancement2->expects(static::once())
            ->method('enhance')
            ->with($enhanced1, $container)
            ->willReturn($enhanced2);

        $definition = (new Transient($provider))
            ->withEnhancement($enhancement2, 200)
            ->withEnhancement($enhancement1, 100);

        static::assertSame($enhanced2, $definition->solve($container));
    }

    public function testSolveWithoutEnhancementsReturnsProviderResultDirectly(): void
    {
        $instance  = new StubClass();
        $provider  = $this->createStub(Provider::class);
        $container = $this->createStub(Container::class);

        $provider->method('provide')
            ->willReturn($instance);

        $definition = new Transient($provider);

        static::assertSame($instance, $definition->solve($container));
    }
}
