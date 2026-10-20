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
use Vivarium\Core\Kernel;

use function chmod;
use function file_get_contents;
use function file_put_contents;
use function ini_get;
use function ini_set;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[CoversClass(Kernel::class)]
final class KernelTest extends TestCase
{
    private string $tmpDir = '';

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/kernel_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
    }

    public function testBootReturnsOneWhenConfigFileIsMissing(): void
    {
        $result = Kernel::boot($this->tmpDir, 'missing.xml');

        self::assertSame(1, $result);
        self::assertFileExists($this->tmpDir . '/kernel.log');
    }

    public function testBootLogsErrorMessageOnFailure(): void
    {
        Kernel::boot($this->tmpDir, 'missing.xml');

        $log = file_get_contents($this->tmpDir . '/kernel.log');

        self::assertNotEmpty($log);
    }

    public function testBootCreatesAppRootDirectoryIfMissing(): void
    {
        $newDir = $this->tmpDir . '/new_dir';

        Kernel::boot($newDir, 'missing.xml');

        self::assertTrue(is_dir($newDir));
    }

    public function testBootFallsBackToErrorLogWhenDirectoryIsNotWritable(): void
    {
        chmod($this->tmpDir, 0555);

        $errorLog = sys_get_temp_dir() . '/kernel_test_error_' . uniqid() . '.log';
        $previous = ini_get('error_log');
        ini_set('error_log', $errorLog);

        $result = Kernel::boot($this->tmpDir, 'missing.xml');

        ini_set('error_log', $previous);
        chmod($this->tmpDir, 0755);

        self::assertSame(1, $result);
        self::assertFileExists($errorLog);
        unlink($errorLog);
    }

    public function testBootReturnsZeroOnSuccessfulBoot(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <config>
                <metadata enabled="false" path=""/>
                <cache enabled="false" path=""/>
                <modules>
                    <module class="Vivarium\Test\Core\Stub\StubModule"/>
                </modules>
            </config>
            XML;

        file_put_contents($this->tmpDir . '/app.xml', $xml);

        $result = Kernel::boot($this->tmpDir, 'app.xml');

        self::assertSame(0, $result);
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $entries = scandir($dir);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . '/' . $entry;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($dir);
    }
}
