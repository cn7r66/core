<?php

declare(strict_types=1);

namespace Vivarium\Test\Container\Provider;

use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Provider\Instance;
use Vivarium\Test\Assertion\Stub\StubClass;

use function in_array;

/** @coversDefaultClass \Vivarium\Container\Provider\Instance */
final class InstanceTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::provide
     */
    public function testProvideReturnsObjectInstance(): void
    {
        $object    = new StubClass();
        $provider  = new Instance($object);
        $container = $this->createMock(Container::class);

        static::assertSame($object, $provider->provide($container));
    }

    /**
     * @covers ::__construct
     * @covers ::provide
     */
    public function testProvideReturnsPrimitiveValue(): void
    {
        $provider  = new Instance(42);
        $container = $this->createMock(Container::class);

        static::assertSame(42, $provider->provide($container));
    }

    /** @covers ::getTarget */
    public function testGetTargetReturnsClassNameForObject(): void
    {
        $provider = new Instance(new StubClass());

        static::assertSame(StubClass::class, $provider->getTarget());
    }

    /** @covers ::getTarget */
    public function testGetTargetReturnsNormalizedTypeForPrimitive(): void
    {
        $provider = new Instance(42);

        static::assertSame('int', $provider->getTarget());
    }

    /**
     * @covers ::__construct
     * @covers ::getCapabilities
     * @dataProvider provideCapabilityCases
     */
    public function testReportsCapabilitiesByValueKind(mixed $value, Capability $capability, bool $expected): void
    {
        $capabilities = (new Instance($value))->getCapabilities();

        static::assertSame($expected, $capabilities->contains($capability));
    }

    /** @return iterable<string, array{mixed, Capability, bool}> */
    public static function provideCapabilityCases(): iterable
    {
        $values = [
            'object' => [new stdClass(), [Capability::INTERCEPTABLE, Capability::DECORABLE]],
            'closure' => [
                static function (): void {
                },
                [Capability::INTERCEPTABLE, Capability::DECORABLE],
            ],
            'callable function name' => ['strlen', [Capability::DECORABLE]],
            'int' => [42, []],
            'non-callable string' => ['hello', []],
            'array' => [[1, 2], []],
        ];

        foreach ($values as $name => [$value, $granted]) {
            foreach (Capability::cases() as $capability) {
                yield $name . ' ' . $capability->name => [
                    $value,
                    $capability,
                    in_array($capability, $granted, true),
                ];
            }
        }
    }
}
