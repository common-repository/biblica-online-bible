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

trait LogUtilities
{
    private ?LoggerInterface $logger = null;

    protected function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            $this->logger = LogManager::createLogger(static::class, BIBLICA_OB_LOG_LEVEL);
        }

        return $this->logger;
    }

    protected function log(string $level, string $message, array $param = []): void
    {
        $this->getLogger()->log($level, $message, $param);
    }

    protected function logException(Exception $e): void
    {
        $this->log(LogLevel::ERROR, '[EXCEPTION: (' . $e->getCode() . ') ' . $e->getMessage() . ']');
        $this->log(LogLevel::DEBUG, '[EXCEPTION TRACE: ' . $e->getTraceAsString() . ']');
    }
}