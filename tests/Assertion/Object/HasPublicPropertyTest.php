<?php

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Object\HasPublicProperty;
use Vivarium\Test\Assertion\Stub\StubClass;

#[CoversClass(HasPublicProperty::class)]
final class HasPublicPropertyTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string|object $class, string $property): void
    {
        static::expectNotToPerformAssertions();

        (new HasPublicProperty($property))
            ->assert($class);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(string|object $class, string $property, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new HasPublicProperty($property))
            ->assert($class);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string|object $class, string $property): void
    {
        static::assertTrue(
            (new HasPublicProperty($property))($class),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string|object $class, string $property): void
    {
        static::assertFalse(
            (new HasPublicProperty($property))($class),
        );
    }

    /** @return array<array{0:object|class-string, 1:string}> */
    public static function provideSuccess(): array
    {
        return [
            [StubClass::class, 'prop'],
            [new StubClass(), 'prop'],
        ];
    }

    /** @return array<array{0:class-string, 1:string, 2:string}> */
    public static function provideFailure(): array
    {
        return [
            [
                StubClass::class,
                'protectedProp',
                'Expected "Vivarium\Test\Assertion\Stub\StubClass" to have a public property named "protectedProp".',
            ],
            [
                StubClass::class,
                'privateProp',
                'Expected "Vivarium\Test\Assertion\Stub\StubClass" to have a public property named "privateProp".',
            ],
            [
                StubClass::class,
                'nonExistentProp',
                'Expected "Vivarium\Test\Assertion\Stub\StubClass" to have a public property named "nonExistentProp".',
            ],
        ];
    }

    /** @return array<array{0:string, 1:string, 2:string}> */
    public static function provideInvalid(): array
    {
        return [
            ['RandomString', 'prop', 'Value must be either class, interface or object. Got "RandomString"'],
        ];
    }
}
