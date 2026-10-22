<?php

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Object\HasProtectedProperty;
use Vivarium\Test\Assertion\Stub\StubClass;

#[CoversClass(HasProtectedProperty::class)]
final class HasProtectedPropertyTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string|object $class, string $property): void
    {
        static::expectNotToPerformAssertions();

        (new HasProtectedProperty($property))
            ->assert($class);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(string|object $class, string $property, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new HasProtectedProperty($property))
            ->assert($class);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string|object $class, string $property): void
    {
        static::assertTrue(
            (new HasProtectedProperty($property))($class),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string|object $class, string $property): void
    {
        static::assertFalse(
            (new HasProtectedProperty($property))($class),
        );
    }

    /** @return array<array{0:object|class-string, 1:string}> */
    public static function provideSuccess(): array
    {
        return [
            [StubClass::class, 'protectedProp'],
            [new StubClass(), 'protectedProp'],
        ];
    }

    /** @return array<array{0:class-string, 1:string, 2:string}> */
    public static function provideFailure(): array
    {
        return [
            [
                StubClass::class,
                'prop',
                'Expected "' . StubClass::class . '" to have a protected property named "prop".',
            ],
            [
                StubClass::class,
                'privateProp',
                'Expected "' . StubClass::class . '" to have a protected property named "privateProp".',
            ],
            [
                StubClass::class,
                'nonExistentProp',
                'Expected "' . StubClass::class . '" to have a protected property named "nonExistentProp".',
            ],
        ];
    }

    /** @return array<array{0:string, 1:string, 2:string}> */
    public static function provideInvalid(): array
    {
        return [
            [
                'RandomString',
                'protectedProp',
                'Value must be either class, interface or object. Got "RandomString"',
            ],
        ];
    }
}
