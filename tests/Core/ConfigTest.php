<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Test\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use Vivarium\Core\Config;

use function file_exists;
use function file_put_contents;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[CoversClass(Config::class)]
final class ConfigTest extends TestCase
{
    private string $tmpFile = '';

    protected function tearDown(): void
    {
        if ($this->tmpFile === '' || ! file_exists($this->tmpFile)) {
            return;
        }

        unlink($this->tmpFile);
    }

    public function testConstructorAndGettersReturnConfiguredValues(): void
    {
        $config = new Config(true, '/meta', false, '/cache', ['Mod\A', 'Mod\B']);

        self::assertTrue($config->isMetadataEnabled());
        self::assertSame('/meta', $config->getMetadataPath());
        self::assertFalse($config->isCacheEnabled());
        self::assertSame('/cache', $config->getCachePath());
        self::assertSame(['Mod\A', 'Mod\B'], $config->getModules());
    }

    public function testLoadFromFileParsesMetadataEnabled(): void
    {
        $config = Config::loadFromFile(__DIR__ . '/Fixture/app.xml');

        self::assertTrue($config->isMetadataEnabled());
    }

    public function testLoadFromFileParsesMetadataPath(): void
    {
        $config = Config::loadFromFile(__DIR__ . '/Fixture/app.xml');

        self::assertSame('/tmp/meta', $config->getMetadataPath());
    }

    public function testLoadFromFileParsesCache(): void
    {
        $config = Config::loadFromFile(__DIR__ . '/Fixture/app.xml');

        self::assertFalse($config->isCacheEnabled());
        self::assertSame('/tmp/cache', $config->getCachePath());
    }

    public function testLoadFromFileParsesModulesSkippingEmptyEntries(): void
    {
        $config = Config::loadFromFile(__DIR__ . '/Fixture/app.xml');

        self::assertSame(['Vivarium\Test\Core\Stub\StubModule'], $config->getModules());
    }

    public function testLoadFromFileThrowsWhenPathDoesNotExist(): void
    {
        $this->expectException(Throwable::class);

        Config::loadFromFile('/nonexistent/path/to/config.xml');
    }

    public function testLoadFromFileThrowsRuntimeExceptionOnInvalidXml(): void
    {
        $this->tmpFile = sys_get_temp_dir() . '/vivarium_config_test_' . uniqid() . '.xml';
        file_put_contents($this->tmpFile, '<?xml version="1.0"?><unclosed>');

        $this->expectException(RuntimeException::class);

        Config::loadFromFile($this->tmpFile);
    }
}
