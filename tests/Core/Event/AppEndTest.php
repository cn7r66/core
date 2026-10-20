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
use Vivarium\Core\Event\AppEnd;
use Vivarium\Dispatcher\Event;
use Vivarium\Dispatcher\NonStoppableEvent;

#[CoversClass(AppEnd::class)]
final class AppEndTest extends TestCase
{
    public function testImplementsEventInterface(): void
    {
        self::assertInstanceOf(Event::class, new AppEnd(0));
    }

    public function testExtendsNonStoppableEvent(): void
    {
        self::assertInstanceOf(NonStoppableEvent::class, new AppEnd(0));
    }

    /** @param int<0, max> $exitCode */
    #[DataProvider('provideValidExitCodes')]
    public function testGetExitCodeReturnsConstructedValue(int $exitCode): void
    {
        $event = new AppEnd($exitCode);

        self::assertSame($exitCode, $event->getExitCode());
    }

    /** @param int<0, max> $exitCode */
    #[DataProvider('provideValidExitCodes')]
    public function testPropagationIsNeverStopped(int $exitCode): void
    {
        $event = new AppEnd($exitCode);

        self::assertFalse($event->isPropagationStopped());
    }

    public function testConstructorRejectsNegativeExitCode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AppEnd(-1);
    }

    public function testConstructorAcceptsZeroExitCode(): void
    {
        $event = new AppEnd(0);

        self::assertSame(0, $event->getExitCode());
    }

    /** @return array<string, array{int}> */
    public static function provideValidExitCodes(): array
    {
        return [
            'exit code 0'   => [0],
            'exit code 1'   => [1],
            'exit code 2'   => [2],
            'exit code 127' => [127],
            'exit code 255' => [255],
        ];
    }
}
