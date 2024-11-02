<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Util;

use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogManager
{
    private static ?string $logFile = null;

    public static function createLogger(string $loggerName, $logLevel = null): LoggerInterface
    {
        if ($logLevel === null || $logLevel === '') {
            $logLevel = LogLevel::ERROR;
        }

        $logDir = WP_CONTENT_DIR . '/log/biblica-online-bible/';
        $logDir = apply_filters('biblica_ob_log_directory', $logDir);
        if (self::$logFile === null) {
            self::$logFile = $logDir . sanitize_text_field($_SERVER['HTTP_HOST']) . '.log';
        }
        $logger = new Logger($loggerName);
        try {
            $handler = new StreamHandler(self::$logFile, $logLevel);
            $handler->setFormatter(new LineFormatter(null, null, false, true));
            $logger->pushHandler($handler);
        } catch (Exception $e) {
        }
        $logger->pushProcessor(new PsrLogMessageProcessor());

        return $logger;
    }
}
