<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Util;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

trait StaticLogUtilities
{
    private static ?LoggerInterface $logger = null;

    protected static function getLogger(): LoggerInterface
    {
        if (self::$logger === null) {
            self::$logger = LogManager::createLogger(static::class, BIBLICA_OB_LOG_LEVEL);
        }

        return self::$logger;
    }

    protected static function log(string $level, string $message, array $param = []): void
    {
        self::getLogger()->log($level, $message, $param);
    }

    protected static function logException(Exception $e): void
    {
        self::log(LogLevel::ERROR, '[EXCEPTION: (' . $e->getCode() . ') ' . $e->getMessage() . ']');
        self::log(LogLevel::DEBUG, '[EXCEPTION TRACE: ' . $e->getTraceAsString() . ']');
    }
}