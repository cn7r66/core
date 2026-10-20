<?php

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Object;

use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Assertion\Object\HasConstant;
use Vivarium\Test\Assertion\Stub\StubClass;

/** @coversDefaultClass \Vivarium\Assertion\Object\HasConstant */
final class HasConstantTest extends TestCase
{
    /**
     * @covers ::__construct()
     * @covers ::assert()
     * @dataProvider provideSuccess()
     */
    public function testAssert(string|object $class, string $constant): void
    {
        static::expectNotToPerformAssertions();

        (new HasConstant($constant))
            ->assert($class);
    }

    /**
     * @covers ::__construct()
     * @covers ::assert()
     * @dataProvider provideFailure()
     * @dataProvider provideInvalid()
     */
    public function testAssertException(string|object $class, string $constant, string $message): void
    {
        static::expectException(AssertionFailed::class);
        static::expectExceptionMessage($message);

        (new HasConstant($constant))
            ->assert($class);
    }

    /**
     * @covers ::__construct()
     * @covers ::__invoke()
     * @dataProvider provideSuccess()
     */
    public function testInvoke(string|object $class, string $constant): void
    {
        static::assertTrue(
            (new HasConstant($constant))($class),
        );
    }

    /**
     * @covers ::__construct()
     * @covers ::__invoke()
     * @dataProvider provideFailure()
     */
    public function testInvokeFailure(string|object $class, string $constant): void
    {
        static::assertFalse(
            (new HasConstant($constant))($class),
        );
    }

    /** @return array<array{0:class-string|object, 1:string}> */
    public static function provideSuccess(): array
    {
        return [
            [StubClass::class, 'INT_CONSTANT'],
            [new StubClass(), 'INT_CONSTANT'],
            [StubClass::class, 'STRING_CONSTANT'],
            [new StubClass(), 'STRING_CONSTANT'],
        ];
    }

    /** @return array<array{0:class-string|object, 1:string, 2:string}> */
    public static function provideFailure(): array
    {
        return [
            [
                StubClass::class,
                'NON_EXISTENT',
                'Expected "' . StubClass::class . '" to have a constant named "NON_EXISTENT".',
            ],
        ];
    }

    /** @return array<array{0:string, 1:string, 2:string}> */
    public static function provideInvalid(): array
    {
        return [
            [
                'RandomString',
                'INT_CONSTANT',
                'Value must be either class, interface or object. Got "RandomString"',
            ],
        ];
    }
}
