<?php

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Object\HasPrivateMethod;
use Vivarium\Test\Assertion\Stub\PrivateConstructorStub;
use Vivarium\Test\Assertion\Stub\ProtectedConstructorStub;
use Vivarium\Test\Assertion\Stub\StubClass;

#[CoversClass(HasPrivateMethod::class)]
final class HasPrivateMethodTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string|object $class, string $method): void
    {
        static::expectNotToPerformAssertions();

        (new HasPrivateMethod($method))
            ->assert($class);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(string|object $class, string $method, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new HasPrivateMethod($method))
            ->assert($class);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string|object $class, string $method): void
    {
        static::assertTrue(
            (new HasPrivateMethod($method))($class),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string|object $class, string $method): void
    {
        static::assertFalse(
            (new HasPrivateMethod($method))($class),
        );
    }

    /** @return array<array{0:object|class-string, 1:string}> */
    public static function provideSuccess(): array
    {
        return [
            [StubClass::class, 'privateMethod'],
            [new StubClass(), 'privateMethod'],
            [PrivateConstructorStub::class, '__construct'],
        ];
    }

    /** @return array<array{0:class-string, 1:string, 2:string}> */
    public static function provideFailure(): array
    {
        return [
            [
                StubClass::class,
                'publicMethod',
                'Expected "' . StubClass::class . '" to have a private method named "publicMethod".',
            ],
            [
                StubClass::class,
                'protectedMethod',
                'Expected "' . StubClass::class . '" to have a private method named "protectedMethod".',
            ],
            [
                StubClass::class,
                'nonExistentMethod',
                'Expected "' . StubClass::class . '" to have a private method named "nonExistentMethod".',
            ],
            [
                StubClass::class,
                '__construct',
                'Expected "' . StubClass::class . '" to have a private method named "__construct".',
            ],
            [
                ProtectedConstructorStub::class,
                '__construct',
                'Expected "' . ProtectedConstructorStub::class . '" to have a private method named "__construct".',
            ],
        ];
    }

    /** @return array<array{0:string, 1:string, 2:string}> */
    public static function provideInvalid(): array
    {
        return [
            [
                'RandomString',
                'privateMethod',
                'Value must be either class, interface or object. Got "RandomString"',
            ],
        ];
    }
}
