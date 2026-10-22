<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Check;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Vivarium\Check\CheckIfType;

#[CoversClass(CheckIfType::class)]
final class CheckIfTypeTest extends CheckTestCase
{
    public const string NAMESPACE = 'Vivarium\Test\Assertion\Type';

    #[DataProvider('provideMethods')]
    public function testCallStatic(string $method): void
    {
        $this->doTest(
            CheckIfType::class,
            $method,
            self::NAMESPACE,
        );
    }

    /** @return array<array<string>> */
    public static function provideMethods(): array
    {
        return [
            ['ImplementsInterface'],
            ['IsAssignableTo'],
            ['IsAssignableToClass'],
            ['IsAssignableToIntersection'],
            ['IsAssignableToPrimitive'],
            ['IsAssignableToUnion'],
            ['IsSubclassOf'],
            ['IsBasicType'],
            ['IsClass'],
            ['IsClassOrInterface'],
            ['IsInterface'],
            ['IsIntersection'],
            ['IsNamespace'],
            ['IsPrimitive'],
            ['IsType'],
            ['IsUnion'],
        ];
    }
}
