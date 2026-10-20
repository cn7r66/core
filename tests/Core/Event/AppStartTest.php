<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Core\Event;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vivarium\Core\Event\AppStart;
use Vivarium\Dispatcher\Event;

#[CoversClass(AppStart::class)]
final class AppStartTest extends TestCase
{
    public function testImplementsEventInterface(): void
    {
        self::assertInstanceOf(Event::class, new AppStart());
    }

    public function testDefaultExitCodeIsZero(): void
    {
        $event = new AppStart();

        self::assertSame(0, $event->getExitCode());
    }

    public function testPropagationIsNotStoppedByDefault(): void
    {
        $event = new AppStart();

        self::assertFalse($event->isPropagationStopped());
    }

    public function testWithExitCodeReturnsNewInstance(): void
    {
        $original = new AppStart();
        $updated  = $original->withExitCode(1);

        self::assertNotSame($original, $updated);
    }

    public function testWithExitCodeDoesNotMutateOriginal(): void
    {
        $original = new AppStart();
        $original->withExitCode(1);

        self::assertSame(0, $original->getExitCode());
    }

    /** @param positive-int $exitCode */
    #[DataProvider('provideNonZeroExitCodes')]
    public function testNewInstanceCarriesUpdatedExitCode(int $exitCode): void
    {
        $event = (new AppStart())->withExitCode($exitCode);

        self::assertSame($exitCode, $event->getExitCode());
    }

    /** @param positive-int $exitCode */
    #[DataProvider('provideNonZeroExitCodes')]
    public function testPropagationIsStoppedWhenExitCodeIsNonZero(int $exitCode): void
    {
        $event = (new AppStart())->withExitCode($exitCode);

        self::assertTrue($event->isPropagationStopped());
    }

    public function testPropagationIsNotStoppedWhenExitCodeIsZero(): void
    {
        $event = (new AppStart())->withExitCode(0);

        self::assertFalse($event->isPropagationStopped());
    }

    public function testWithExitCodeRejectsNegativeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new AppStart())->withExitCode(-1);
    }

    public function testWithExitCodeAcceptsZero(): void
    {
        $event = (new AppStart())->withExitCode(0);

        self::assertSame(0, $event->getExitCode());
    }

    /** @return array<string, array{int}> */
    public static function provideNonZeroExitCodes(): array
    {
        return [
            'exit code 1'   => [1],
            'exit code 2'   => [2],
            'exit code 127' => [127],
            'exit code 255' => [255],
        ];
    }
}
