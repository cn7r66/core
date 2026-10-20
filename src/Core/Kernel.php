<?php

declare(strict_types=1);

/*
 * This file is part of Vivarium
 * SPDX-License-Identifier: MPL-2.0
 * Copyright (c) The Vivarium Project
 */

namespace Vivarium\Core;

use DateTime;
use Throwable;
use Vivarium\Assertion\Conditional\Not;
use Vivarium\Assertion\String\IsEmpty;
use Vivarium\Assertion\Type\IsAssignableTo;
use Vivarium\Container\Injector;
use Vivarium\Container\Registry\EagerRegistry;
use Vivarium\Container\Registry\LazyRegistry;
use Vivarium\Core\Event\AppEnd;
use Vivarium\Core\Event\AppStart;
use Vivarium\Dispatcher\EventDispatcher;

use function error_log;
use function file_put_contents;
use function implode;
use function is_dir;
use function mkdir;
use function sprintf;
use function strlen;

use const DIRECTORY_SEPARATOR;
use const FILE_APPEND;
use const LOCK_EX;
use const PHP_EOL;

final class Kernel
{
    public static function boot(string $appRoot, string $appConfig): int
    {
        try {
            (new Not(new IsEmpty()))
                ->assert($appRoot);

            (new Not(new IsEmpty()))
                ->assert($appConfig);

            return self::start(
                Config::loadFromFile(implode(DIRECTORY_SEPARATOR, [$appRoot, $appConfig])),
            );
        } catch (Throwable $ex) {
            $error = sprintf(
                '[%s] %s',
                (new DateTime())->format('d/M/Y:H:i:s O'),
                $ex->getMessage(),
            );

            if (strlen($appRoot) > 0 && ! is_dir($appRoot)) {
                mkdir($appRoot, 0755, true);
            }

            if (is_dir($appRoot)) {
                $result = @file_put_contents(
                    implode(DIRECTORY_SEPARATOR, [$appRoot, 'kernel.log']),
                    [$error, PHP_EOL],
                    FILE_APPEND | LOCK_EX,
                );
            }

            if (! isset($result) || $result === false) {
                error_log($error);
            }

            return 1;
        }
    }

    private static function start(Config $config): int
    {
        $injector = new Injector(new LazyRegistry(static function () use ($config) {
            $registry = new EagerRegistry();

            $class = $registry::class;
            foreach ($config->getModules() as $module) {
                $registry = (new $module())->configure($registry);

                (new IsAssignableTo($class))
                    ->assert($registry::class);
            }

            return $registry;
        }));

        $dispatcher = $injector->get(EventDispatcher::class);

        $exitCode = $dispatcher
            ->dispatch(new AppStart())
            ->getExitCode();

        $exitCode = $dispatcher
            ->dispatch(new AppEnd($exitCode))
            ->getExitCode();

        return $exitCode;
    }
}
