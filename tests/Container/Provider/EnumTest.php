<?php

declare(strict_types=1);

namespace Vivarium\Test\Container\Provider;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Provider\Enum;
use Vivarium\Container\Scope;

#[CoversClass(Enum::class)]
final class EnumTest extends TestCase
{
    public function testConstructWithValidEnum(): void
    {
        static::expectNotToPerformAssertions();

        new Enum(Scope::class, 'SERVICE');
    }

    public function testConstructWithInvalidEnumThrows(): void
    {
        static::expectException(AssertionFailed::class);

        new Enum('NotAnEnum', 'VALUE');
    }

    public function testConstructWithInvalidCaseThrows(): void
    {
        static::expectException(AssertionFailed::class);

        new Enum(Scope::class, 'INVALID_CASE');
    }

    public function testProvideReturnsEnumCase(): void
    {
        $provider  = new Enum(Scope::class, 'SERVICE');
        $container = $this->createStub(Container::class);

        static::assertSame(Scope::SERVICE, $provider->provide($container));
    }

    public function testGetTargetReturnsEnumClassName(): void
    {
        $provider = new Enum(Scope::class, 'TRANSIENT');

        static::assertSame(Scope::class, $provider->getTarget());
    }

    public function testGetCapabilitiesReturnsEmptySet(): void
    {
        $provider     = new Enum(Capability::class, 'INJECTABLE');
        $capabilities = $provider->getCapabilities();

        static::assertCount(0, $capabilities);
    }
}
