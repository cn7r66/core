<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Container\Binding;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Vivarium\Container\Binding\MethodBinder;
use Vivarium\Container\Caller;

#[CoversClass(MethodBinder::class)]
final class MethodBinderTest extends TestCase
{
    public function testMethodForwardsMethodNameToCreate(): void
    {
        $capturedMethod = null;

        $binder = new MethodBinder(
            static function (string $method, callable $configure) use (&$capturedMethod): string {
                $capturedMethod = $method;

                return 'result';
            },
        );

        $binder->method('setFoo');

        static::assertSame('setFoo', $capturedMethod);
    }

    public function testMethodWithoutConfigureUsesIdentityFunction(): void
    {
        $capturedConfig = null;

        $binder = new MethodBinder(
            static function (string $method, callable $configure) use (&$capturedConfig): string {
                $capturedConfig = $configure;

                return 'result';
            },
        );

        $binder->method('setFoo');

        $caller = $this->createStub(Caller::class);

        static::assertSame($caller, $capturedConfig($caller));
    }

    public function testMethodWithConfigureForwardsCallbackToCreate(): void
    {
        $configure      = static fn (Caller $caller): Caller => $caller;
        $capturedConfig = null;

        $binder = new MethodBinder(
            static function (string $method, callable $cfg) use (&$capturedConfig): string {
                $capturedConfig = $cfg;

                return 'result';
            },
        );

        $binder->method('setFoo', $configure);

        static::assertSame($configure, $capturedConfig);
    }

    public function testMethodReturnsValueFromCreate(): void
    {
        $expected = new stdClass();

        $binder = new MethodBinder(
            static fn (string $method, callable $configure): object => $expected,
        );

        static::assertSame($expected, $binder->method('setFoo'));
    }
}
