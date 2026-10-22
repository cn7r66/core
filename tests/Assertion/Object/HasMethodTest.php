<?php

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Object\HasMethod;
use Vivarium\Test\Assertion\Stub\StubClass;

#[CoversClass(HasMethod::class)]
final class HasMethodTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string|object $class, string $method): void
    {
        static::expectNotToPerformAssertions();

        (new HasMethod($method))
            ->assert($class);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(string|object $class, string $method, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new HasMethod($method))
            ->assert($class);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string|object $class, string $method): void
    {
        static::assertTrue(
            (new HasMethod($method))($class),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string|object $class, string $method): void
    {
        static::assertFalse(
            (new HasMethod($method))($class),
        );
    }

    /** @return array<array{0:object|class-string, 1:string}> */
    public static function provideSuccess(): array
    {
        return [
            [StubClass::class, '__toString'],
            [new StubClass(), '__toString'],
            [StubClass::class, 'publicMethod'],
            [new StubClass(), 'publicMethod'],
            [StubClass::class, 'protectedMethod'],
            [new StubClass(), 'protectedMethod'],
            [StubClass::class, 'privateMethod'],
            [new StubClass(), 'privateMethod'],
        ];
    }

    /** @return array<array{0:class-string, 1:string, 2:string}> */
    public static function provideFailure(): array
    {
        return [
            [stdClass::class, '__toString', 'Expected "stdClass" to have a method named "__toString".'],
        ];
    }

    /** @return array<array{0:string, 1:string, 2:string}> */
    public static function provideInvalid(): array
    {
        return [
            ['RandomString', '__toString', 'Value must be either class, interface or object. Got "RandomString"'],
        ];
    }
}
