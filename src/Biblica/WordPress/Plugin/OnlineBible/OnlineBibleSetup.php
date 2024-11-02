<?php

/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Biblica\WordPress\Plugin\OnlineBible;

use Biblica\Util\CacheManager;

class OnlineBibleSetup
{
    public static function deactivate(): void
    {
        CacheManager::clearCacheDirectory();
    }

    public static function onDeactivate(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        self::deactivate();
    }

    public static function activate(): void
    {
        add_option(Settings::$onlineBibleOptionName, Settings::$onlineBibleOptionValues);
        add_option(Settings::$translationOptionName, Settings::$translationOptionValues);
    }

    public static function onActivate(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        self::activate();
    }

    public static function uninstall(): void
    {
        delete_option(Settings::$onlineBibleOptionName);
        delete_option(Settings::$translationOptionName);
    }

    public static function onUninstall(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        self::uninstall();
    }
}
