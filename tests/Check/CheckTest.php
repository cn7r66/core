<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Check;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Vivarium\Check\Check;
use Vivarium\Check\Exception\NoSuchMethod;
use Vivarium\Check\Exception\TooFewArguments;
use Vivarium\Check\Exception\TooMuchArguments;

#[CoversClass(Check::class)]
final class CheckTest extends CheckTestCase
{
    public const COMPARISON_NAMESPACE = 'Vivarium\Test\Assertion\Comparison';
    public const ENCODING_NAMESPACE   = 'Vivarium\Test\Assertion\Encoding';
    public const NUMERIC_NAMESPACE    = 'Vivarium\Test\Assertion\Numeric';
    public const OBJECT_NAMESPACE     = 'Vivarium\Test\Assertion\Object';
    public const STRING_NAMESPACE     = 'Vivarium\Test\Assertion\String';
    public const TYPE_NAMESPACE       = 'Vivarium\Test\Assertion\Type';
    public const VAR_NAMESPACE        = 'Vivarium\Test\Assertion\Var';

    public function testBoolean(): void
    {
        $check = Check::boolean();

        // @phpstan-ignore method.notFound
        static::assertTrue($check->isTrue(true));

        // @phpstan-ignore method.notFound
        static::assertTrue($check->isFalse(false));

        // @phpstan-ignore method.notFound
        static::assertFalse($check->isTrue(false));

        // @phpstan-ignore method.notFound
        static::assertFalse($check->isFalse(true));
    }

    #[DataProviderExternal(CheckIfElementTest::class, 'provideMethods')]
    public function testComparison(string $method): void
    {
        $this->doTest(
            Check::comparison(),
            $method,
            self::COMPARISON_NAMESPACE,
        );
    }

    #[DataProviderExternal(CheckIfEncodingTest::class, 'provideMethods')]
    public function testEncoding(string $method): void
    {
        $this->doTest(
            Check::encoding(),
            $method,
            self::ENCODING_NAMESPACE,
        );
    }

    #[DataProviderExternal(CheckIfNumberTest::class, 'provideMethods')]
    public function testNumeric(string $method): void
    {
        $this->doTest(
            Check::numeric(),
            $method,
            self::NUMERIC_NAMESPACE,
        );
    }

    #[DataProviderExternal(CheckIfObjectTest::class, 'provideMethods')]
    public function testObject(string $method): void
    {
        $this->doTest(
            Check::object(),
            $method,
            self::OBJECT_NAMESPACE,
        );
    }

    #[DataProviderExternal(CheckIfStringTest::class, 'provideMethods')]
    public function testString(string $method): void
    {
        $this->doTest(
            Check::string(),
            $method,
            self::STRING_NAMESPACE,
        );
    }

        #[DataProviderExternal(CheckIfTypeTest::class, 'provideMethods')]
    public function testType(string $method): void
    {
        $this->doTest(
            Check::type(),
            $method,
            self::TYPE_NAMESPACE,
        );
    }

        #[DataProviderExternal(CheckIfVarTest::class, 'provideMethods')]
    public function testVar(string $method): void
    {
        $this->doTest(
            Check::var(),
            $method,
            self::VAR_NAMESPACE,
        );
    }

    public function testNoSuchMethod(): void
    {
        static::expectException(NoSuchMethod::class);
        static::expectExceptionMessage(
            'No such method nonExistentMethod. Missing class Vivarium\Assertion\String\NonExistentMethod',
        );

        $check = Check::string();

        // @phpstan-ignore method.notFound
        $check->nonExistentMethod();
    }

    public function testTooFewArguments(): void
    {
        static::expectException(TooFewArguments::class);
        static::expectExceptionMessage(
            'Too few arguments provided. Expected 1, got 0',
        );

        $check = Check::boolean();

        // @phpstan-ignore method.notFound
        $check->isTrue();
    }

    public function testTooMuchArguments(): void
    {
        static::expectException(TooMuchArguments::class);
        static::expectExceptionMessage(
            'Too much arguments provided. Expected 1, got 2',
        );

        $check = Check::boolean();

        // @phpstan-ignore method.notFound
        $check->isTrue(true, false);
    }
}
