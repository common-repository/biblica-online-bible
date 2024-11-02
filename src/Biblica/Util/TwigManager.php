<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\Util;

use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

class TwigManager
{
    public static function createEnvironment(
        string $templateLocation,
        bool $cache = true,
        bool $debug = false
    ): Environment {
        $options = [];
        if ($cache === true && CacheManager::checkCacheDirectory()) {
            $options['cache'] = self::getTwigCacheDirectory();
        }
        if ($debug) {
            $options['debug'] = true;
        }

        $loader = new FilesystemLoader($templateLocation);
        $environment = new Environment($loader, $options);

        if ($debug) {
            $environment->addExtension(new DebugExtension());
        }

        return $environment;
    }

    public static function clearCache(): bool
    {
        return FileManager::removeDirectory(self::getTwigCacheDirectory());
    }

    public static function getTwigCacheDirectory(): string
    {
        return CacheManager::getCacheDirectory() . 'twig';
    }
}
