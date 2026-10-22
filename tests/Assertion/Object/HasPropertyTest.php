<?php

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Object\HasProperty;
use Vivarium\Test\Assertion\Stub\StubClass;

#[CoversClass(HasProperty::class)]
final class HasPropertyTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string|object $class, string $method): void
    {
        static::expectNotToPerformAssertions();

        (new HasProperty($method))
            ->assert($class);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(string|object $class, string $method, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new HasProperty($method))
            ->assert($class);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string|object $class, string $method): void
    {
        static::assertTrue(
            (new HasProperty($method))($class),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string|object $class, string $method): void
    {
        static::assertFalse(
            (new HasProperty($method))($class),
        );
    }

    /** @return array<array{0:class-string|object, 1:string}> */
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
            [stdClass::class, 'prop', 'Expected "stdClass" to have a property named "prop".'],
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
