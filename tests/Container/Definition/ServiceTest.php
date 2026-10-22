<?php

declare(strict_types=1);

namespace Vivarium\Test\Container\Definition;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Container\Container;
use Vivarium\Container\Definition\Service;
use Vivarium\Container\Enhancement;
use Vivarium\Container\Provider;
use Vivarium\Test\Assertion\Stub\StubClass;

#[CoversClass(Service::class)]
final class ServiceTest extends TestCase
{
    public function testSolveReturnsSameInstanceOnMultipleCalls(): void
    {
        $instance  = new StubClass();
        $provider  = $this->createMock(Provider::class);
        $container = $this->createStub(Container::class);

        $provider->expects(static::once())
            ->method('provide')
            ->with($container)
            ->willReturn($instance);

        $definition = new Service($provider);

        $result1 = $definition->solve($container);
        $result2 = $definition->solve($container);

        static::assertSame($instance, $result1);
        static::assertSame($instance, $result2);
        static::assertSame($result1, $result2);
    }

    public function testSolveCallsProviderOnlyOnce(): void
    {
        $provider  = $this->createMock(Provider::class);
        $container = $this->createStub(Container::class);

        $provider->expects(static::once())
            ->method('provide')
            ->willReturn(new StubClass());

        $definition = new Service($provider);

        $definition->solve($container);
        $definition->solve($container);
        $definition->solve($container);
    }

    public function testWithEnhancementReturnsNewInstance(): void
    {
        $provider    = $this->createStub(Provider::class);
        $enhancement = $this->createStub(Enhancement::class);

        $definition1 = new Service($provider);
        $definition2 = $definition1->withEnhancement($enhancement, 100);

        static::assertNotSame($definition1, $definition2);
    }

    public function testSolveAppliesEnhancementsAndCachesResult(): void
    {
        $instance  = new StubClass();
        $enhanced  = new StubClass();
        $provider  = $this->createMock(Provider::class);
        $container = $this->createStub(Container::class);

        $provider->expects(static::once())
            ->method('provide')
            ->willReturn($instance);

        $enhancement = $this->createMock(Enhancement::class);
        $enhancement->expects(static::once())
            ->method('enhance')
            ->with($instance, $container)
            ->willReturn($enhanced);

        $definition = (new Service($provider))
            ->withEnhancement($enhancement, 100);

        $result1 = $definition->solve($container);
        $result2 = $definition->solve($container);

        static::assertSame($enhanced, $result1);
        static::assertSame($enhanced, $result2);
    }

    public function testWithEnhancementDoesNotAffectOriginalInstance(): void
    {
        $instance  = new StubClass();
        $enhanced  = new StubClass();
        $provider  = $this->createStub(Provider::class);
        $container = $this->createStub(Container::class);

        $provider->method('provide')
            ->willReturn($instance);

        $enhancement = $this->createStub(Enhancement::class);
        $enhancement->method('enhance')
            ->willReturn($enhanced);

        $definition1 = new Service($provider);
        $definition2 = $definition1->withEnhancement($enhancement, 100);

        $result1 = $definition1->solve($container);
        $result2 = $definition2->solve($container);

        static::assertSame($instance, $result1);
        static::assertSame($enhanced, $result2);
    }
}
