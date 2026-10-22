<?php

declare(strict_types=1);

namespace Vivarium\Test\Container\Provider;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Container;
use Vivarium\Container\Provider\Constant;

use const PHP_VERSION;

#[CoversClass(Constant::class)]
final class ConstantTest extends TestCase
{
    public function testConstructWithValidConstant(): void
    {
        static::expectNotToPerformAssertions();

        new Constant('PHP_VERSION');
    }

    public function testConstructWithInvalidConstantThrows(): void
    {
        static::expectException(AssertionFailed::class);

        new Constant('NONEXISTENT_CONSTANT');
    }

    public function testProvideReturnsConstantValue(): void
    {
        $provider  = new Constant('PHP_VERSION');
        $container = $this->createStub(Container::class);

        static::assertSame(PHP_VERSION, $provider->provide($container));
    }

    public function testGetTargetReturnsNormalizedType(): void
    {
        $provider = new Constant('PHP_VERSION');

        static::assertSame('string', $provider->getTarget());
    }

    public function testGetCapabilitiesReturnsEmptySet(): void
    {
        $provider     = new Constant('PHP_VERSION');
        $capabilities = $provider->getCapabilities();

        static::assertCount(0, $capabilities);
    }
}
