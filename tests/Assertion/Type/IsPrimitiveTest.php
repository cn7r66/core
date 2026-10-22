<?php

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Type\IsPrimitive;
use Vivarium\Test\Assertion\Stub\Stub;

#[CoversClass(IsPrimitive::class)]
final class IsPrimitiveTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string $type): void
    {
        static::expectNotToPerformAssertions();

        (new IsPrimitive())
            ->assert($type);
    }

    #[DataProvider('provideFailure')]
    public function testAssertException(string $type, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsPrimitive())
            ->assert($type);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string $type): void
    {
        static::assertTrue(
            (new IsPrimitive())($type),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string $type): void
    {
        static::assertFalse(
            (new IsPrimitive())($type),
        );
    }

    /** @return array<array<string>> */
    public static function provideSuccess(): array
    {
        return [
            ['int'],
            ['integer'],
            ['float'],
            ['double'],
            ['string'],
            ['bool'],
            ['boolean'],
            ['array'],
            ['callable'],
            ['object'],
        ];
    }

    /** @return array<array<string>> */
    public static function provideFailure(): array
    {
        return [
            ['RandomString', 'Expected string to be a primitive type. Got "RandomString".'],
            [Stub::class, 'Expected string to be a primitive type. Got "Vivarium\Test\Assertion\Stub\Stub".'],
        ];
    }
}
