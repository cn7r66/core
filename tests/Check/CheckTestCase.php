<?php

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

declare(strict_types=1);

namespace Vivarium\Test\Check;

use PHPUnit\Framework\TestCase;
use Vivarium\Assertion\Var\IsCallable;

use function array_slice;
use function call_user_func;
use function call_user_func_array;
use function count;
use function ucfirst;

abstract class CheckTestCase extends TestCase
{
    public const string SUCCESS = 'provideSuccess';

    public const string FAILURE = 'provideFailure';

    protected function doTest(string|object $class, string $method, string $namespace): void
    {
        $this->doTestSuccess($class, $method, $namespace);
        $this->doTestFailure($class, $method, $namespace);
    }

    private function doTestSuccess(string|object $class, string $method, string $namespace): void
    {
        $fn = [$class, $method];
        (new IsCallable())
            ->assert($fn);

        foreach ($this->getTests($namespace, $method, self::SUCCESS) as $test) {
            static::assertTrue(
                call_user_func_array($fn, $test),
            );
        }
    }

    private function doTestFailure(string|object $class, string $method, string $namespace): void
    {
        $fn = [$class, $method];
        (new IsCallable())
            ->assert($fn);

        foreach ($this->getTests($namespace, $method, self::FAILURE) as $test) {
            static::assertFalse(
                call_user_func_array(
                    $fn,
                    array_slice($test, 0, count($test) - 1),
                ),
            );
        }
    }

    /** @return array<array<mixed>> */
    private function getTests(string $namespace, string $method, string $provider): array
    {
        $fn = [$namespace . '\\' . ucfirst($method) . 'Test', $provider];
        (new IsCallable())
            ->assert($fn);

        /** @psalm-var array<array<mixed>> */
        return call_user_func($fn);
    }
}
