<?php

declare(strict_types=1);

namespace Vivarium\Test\Container\Provider;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Container\Capability;
use Vivarium\Container\Container;
use Vivarium\Container\Provider\Instance;
use Vivarium\Test\Assertion\Stub\StubClass;

use function in_array;

#[CoversClass(Instance::class)]
final class InstanceTest extends TestCase
{
    #[DataProvider('provideWrappedValues')]
    public function testProvideReturnsWrappedValue(mixed $value): void
    {
        $provider  = new Instance($value);
        $container = $this->createStub(Container::class);

        static::assertSame($value, $provider->provide($container));
    }

    /** @return array<string, array{mixed}> */
    public static function provideWrappedValues(): array
    {
        return [
            'object' => [new StubClass()],
            'primitive' => [42],
        ];
    }

    #[DataProvider('provideValuesAndTheirTargets')]
    public function testGetTargetReturnsTypeOfValue(mixed $value, string $expected): void
    {
        static::assertSame($expected, (new Instance($value))->getTarget());
    }

    /** @return array<string, array{mixed, string}> */
    public static function provideValuesAndTheirTargets(): array
    {
        return [
            'class name for object' => [new StubClass(), StubClass::class],
            'normalized type for primitive' => [42, 'int'],
        ];
    }

    #[DataProvider('provideCapabilityCases')]
    public function testReportsCapabilitiesByValueKind(mixed $value, Capability $capability, bool $expected): void
    {
        $capabilities = (new Instance($value))->getCapabilities();

        static::assertSame($expected, $capabilities->contains($capability));
    }

    /** @return array<string, array{mixed, Capability, bool}> */
    public static function provideCapabilityCases(): array
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

        $cases = [];
        foreach ($values as $name => [$value, $granted]) {
            foreach (Capability::cases() as $capability) {
                $cases[$name . ' ' . $capability->name] = [
                    $value,
                    $capability,
                    in_array($capability, $granted, true),
                ];
            }
        }

        return $cases;
    }
}
