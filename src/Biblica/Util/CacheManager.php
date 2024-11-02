<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Util;

use Exception;

class CacheManager
{
    private static ?CacheInterface $cache = null;
    private static ?string $cacheDirectory = null;

    public static function setObjectCache(CacheInterface $cache): void
    {
        self::$cache = $cache;
    }

    /**
     * @throws Exception
     */
    public static function getObjectCache(): CacheInterface
    {
        if (self::$cache === null) {
            throw new Exception('Cache service not set in ' . __CLASS__);
        }

        return self::$cache;
    }

    public static function getObjectCacheDirectory(): string
    {
        return self::getCacheDirectory() . 'object';
    }

    public static function clearObjectCacheDirectory(): bool
    {
        return FileManager::removeDirectory(self::getObjectCacheDirectory());
    }

    public static function clearCacheDirectory(): bool
    {
        return FileManager::removeDirectory(self::getCacheDirectory());
    }

    public static function checkCacheDirectory(): bool
    {
        if (!is_writable(WP_CONTENT_DIR)) {
            return false;
        }
        if (!is_dir(self::getCacheDirectory())) {
            mkdir(self::getCacheDirectory());
        }
        if (!is_dir(self::getCacheDirectory()) || !is_writable(self::getCacheDirectory())) {
            return false;
        }

        return true;
    }

    public static function getCacheDirectory(): string
    {
        if (self::$cacheDirectory === null) {
            self::$cacheDirectory = WP_CONTENT_DIR . '/cache/biblica-online-bible/';
        }

        return self::$cacheDirectory;
    }
}
