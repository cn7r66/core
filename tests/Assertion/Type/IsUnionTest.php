<?php

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Type\IsUnion;
use Vivarium\Test\Assertion\Stub\StubClass;

#[CoversClass(IsUnion::class)]
final class IsUnionTest extends TestCase
{
    #[DataProvider('provideSuccess')]
    public function testAssert(string $type): void
    {
        static::expectNotToPerformAssertions();

        (new IsUnion())
            ->assert($type);
    }

    #[DataProvider('provideFailure')]
    #[DataProvider('provideInvalid')]
    public function testAssertException(string|int $type, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new IsUnion())
            ->assert($type);
    }

    #[DataProvider('provideSuccess')]
    public function testInvoke(string $type): void
    {
        static::assertTrue(
            (new IsUnion())($type),
        );
    }

    #[DataProvider('provideFailure')]
    public function testInvokeFailure(string $type): void
    {
        static::assertFalse(
            (new IsUnion())($type),
        );
    }

    /** @return array<array<string>> */
    public static function provideSuccess(): array
    {
        return [
            [stdClass::class . '|' . StubClass::class],
            ['int|float'],
            ['int|' . stdClass::class],
        ];
    }

    /** @return array<array<string>> */
    public static function provideFailure(): array
    {
        return [
            [
                'Foo|Bar',
                'Expected string to be union. Got "Foo|Bar".',
            ],
            [
                stdClass::class,
                'Expected string to be union. Got "stdClass".',
            ],
        ];
    }

    /** @return array<array{0:int, 1:string}> */
    public static function provideInvalid(): array
    {
        return [
            [42, 'Expected value to be string. Got int.'],
        ];
    }
}
