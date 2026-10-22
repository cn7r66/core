<?php

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Object\HasPublicMethod;
use Vivarium\Test\Assertion\Stub\PrivateConstructorStub;
use Vivarium\Test\Assertion\Stub\ProtectedConstructorStub;
use Vivarium\Test\Assertion\Stub\StubClass;
use Vivarium\Test\Assertion\Stub\StubClassWithConstructor;

#[CoversClass(HasPublicMethod::class)]
final class HasPublicMethodTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string|object $class, string $method): void
    {
        static::expectNotToPerformAssertions();

        (new HasPublicMethod($method))
            ->assert($class);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(string|object $class, string $method, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new HasPublicMethod($method))
            ->assert($class);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string|object $class, string $method): void
    {
        static::assertTrue(
            (new HasPublicMethod($method))($class),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string|object $class, string $method): void
    {
        static::assertFalse(
            (new HasPublicMethod($method))($class),
        );
    }

    /** @return array<array{0:object|class-string, 1:string}> */
    public static function provideSuccess(): array
    {
        return [
            [StubClass::class, 'publicMethod'],
            [new StubClass(), 'publicMethod'],
            [StubClassWithConstructor::class, '__construct'],
            [new StubClassWithConstructor(), '__construct'],
        ];
    }

    /** @return array<array{0:class-string, 1:string, 2:string}> */
    public static function provideFailure(): array
    {
        return [
            [
                StubClass::class,
                'protectedMethod',
                'Expected "' . StubClass::class . '" to have a public method named "protectedMethod".',
            ],
            [
                StubClass::class,
                'privateMethod',
                'Expected "' . StubClass::class . '" to have a public method named "privateMethod".',
            ],
            [
                StubClass::class,
                'nonExistentMethod',
                'Expected "' . StubClass::class . '" to have a public method named "nonExistentMethod".',
            ],
            [
                StubClass::class,
                '__construct',
                'Expected "' . StubClass::class . '" to have a public method named "__construct".',
            ],
            [
                ProtectedConstructorStub::class,
                '__construct',
                'Expected "' . ProtectedConstructorStub::class . '" to have a public method named "__construct".',
            ],
            [
                PrivateConstructorStub::class,
                '__construct',
                'Expected "' . PrivateConstructorStub::class . '" to have a public method named "__construct".',
            ],
        ];
    }

    /** @return array<array{0:string, 1:string, 2:string}> */
    public static function provideInvalid(): array
    {
        return [
            [
                'RandomString',
                'publicMethod',
                'Value must be either class, interface or object. Got "RandomString"',
            ],
        ];
    }
}
