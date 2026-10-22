<?php

declare(strict_types=1);

namespace Vivarium\Test\Container\Provider;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Container\Container;
use Vivarium\Container\Provider\ClassConstant;
use Vivarium\Test\Container\Stub\StubClass;

#[CoversClass(ClassConstant::class)]
final class ClassConstantTest extends TestCase
{
    public function testConstructWithValidClassConstant(): void
    {
        static::expectNotToPerformAssertions();

        new ClassConstant(StubClass::class, 'INT_CONSTANT');
    }

    public function testConstructWithInvalidClassThrows(): void
    {
        static::expectException(AssertionFailed::class);

        new ClassConstant('NotAClass', 'CONSTANT');
    }

    public function testConstructWithInvalidConstantThrows(): void
    {
        static::expectException(AssertionFailed::class);

        new ClassConstant(StubClass::class, 'NONEXISTENT');
    }

    public function testProvideReturnsConstantValue(): void
    {
        $provider  = new ClassConstant(StubClass::class, 'STRING_CONSTANT');
        $container = $this->createStub(Container::class);

        static::assertSame('hello', $provider->provide($container));
    }

    #[DataProvider('constantTypeProvider')]
    public function testGetTargetReturnsNormalizedType(string $constantName, string $expectedType): void
    {
        $provider = new ClassConstant(StubClass::class, $constantName);

        static::assertSame($expectedType, $provider->getTarget());
    }

    /** @return array<string, array{string, string}> */
    public static function constantTypeProvider(): array
    {
        return [
            'int constant'    => ['INT_CONSTANT', 'int'],
            'string constant' => ['STRING_CONSTANT', 'string'],
            'float constant'  => ['FLOAT_CONSTANT', 'float'],
            'bool constant'   => ['BOOL_CONSTANT', 'bool'],
            'null constant'   => ['NULL_CONSTANT', 'null'],
            'array constant'  => ['ARRAY_CONSTANT', 'array'],
        ];
    }

    public function testGetCapabilitiesReturnsEmptySet(): void
    {
        $provider     = new ClassConstant(StubClass::class, 'INT_CONSTANT');
        $capabilities = $provider->getCapabilities();

        static::assertCount(0, $capabilities);
    }
}
