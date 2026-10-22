<?php

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Object\HasPrivateProperty;
use Vivarium\Test\Assertion\Stub\StubClass;

#[CoversClass(HasPrivateProperty::class)]
final class HasPrivatePropertyTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string|object $class, string $property): void
    {
        static::expectNotToPerformAssertions();

        (new HasPrivateProperty($property))
            ->assert($class);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(string|object $class, string $property, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new HasPrivateProperty($property))
            ->assert($class);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string|object $class, string $property): void
    {
        static::assertTrue(
            (new HasPrivateProperty($property))($class),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string|object $class, string $property): void
    {
        static::assertFalse(
            (new HasPrivateProperty($property))($class),
        );
    }

    /** @return array<array{0:object|class-string, 1:string}> */
    public static function provideSuccess(): array
    {
        return [
            [StubClass::class, 'privateProp'],
            [new StubClass(), 'privateProp'],
        ];
    }

    /** @return array<array{0:class-string, 1:string, 2:string}> */
    public static function provideFailure(): array
    {
        return [
            [
                StubClass::class,
                'prop',
                'Expected "' . StubClass::class . '" to have a private property named "prop".',
            ],
            [
                StubClass::class,
                'protectedProp',
                'Expected "' . StubClass::class . '" to have a private property named "protectedProp".',
            ],
            [
                StubClass::class,
                'nonExistentProp',
                'Expected "' . StubClass::class . '" to have a private property named "nonExistentProp".',
            ],
        ];
    }

    /** @return array<array{0:string, 1:string, 2:string}> */
    public static function provideInvalid(): array
    {
        return [
            [
                'RandomString',
                'privateProp',
                'Value must be either class, interface or object. Got "RandomString"',
            ],
        ];
    }
}
