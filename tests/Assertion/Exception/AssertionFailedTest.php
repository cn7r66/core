<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Assertion\Exception;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Exception\AssertionFailed;
use Vivarium\Failure\Failure;

#[CoversClass(AssertionFailed::class)]
final class AssertionFailedTest extends TestCase
{
    public function testConstructor(): void
    {
        $failure = new Failure('Expected {name}. Got {value}.', 1, name: 'x');

        $exception = new AssertionFailed($failure);

        self::assertInstanceOf(InvalidArgumentException::class, $exception);
        self::assertSame('Expected "x". Got int 1.', $exception->getMessage());
    }

    public function testGetFailure(): void
    {
        $failure = new Failure('Got {value}.', 1);

        self::assertSame($failure, (new AssertionFailed($failure))->getFailure());
    }
}
